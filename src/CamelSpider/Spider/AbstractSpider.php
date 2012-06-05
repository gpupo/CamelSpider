<?php

namespace CamelSpider\Spider;

use CamelSpider\Entity\AbstractSpiderEgg,
    CamelSpider\Entity\Pool,
    Symfony\Component\DomCrawler\Form;

abstract class AbstractSpider extends AbstractSpiderEgg
{
    protected $backendLogger = '';
    protected $cached = 0;
    protected $errors = 0;
    protected $goutte;
    protected $hiperlinks = 0;
    protected $limitReached = false;
    protected $logger_level = 1;
    protected $name = 'Spider';
    protected $time = array('total' => 0, 'parcial' => 0);
    protected $pool;
    protected $requests = 0;
    protected $subscription;
    protected $success = 0;
    protected $timeParcial;

    public function debug()
    {
        echo $this->getSummary();

        echo  $this->getBackendLogger();
    }

    /**
     * Convert string of auth information into a array
     *
     * Sample auth info (one parameter per line):
     *  "type":"form"
     *  "button":"log in"
     *  "username":"gpupo"
     *  "password":"mypassword"
     *  "expected":"a word finded on sucesseful login"
     *  "password_input": "field_name"
     *  "username_input": 'field_name"
     *
     *  or only one line:
     *  "button":"log in", "username":"gpupo", "password":"mypassword", "expected":"a word finded on sucesseful login"
     *

     *
     * @param string $string
     * @return array $a
     */
    public function getAuthCredentials($string)
    {
        $json = '{' . str_replace(PHP_EOL, ',', trim($string)) . '}';
        $a =  json_decode($json);
        if (is_null($a)) {
            throw new \Exception('Invalid credentials syntaxe. Received: ' . trim($string) . "\n" . $json);
        }

        $credentials = (array) $a;

        if (count($credentials) < 1) {
            throw new \Exception('Missing credentials information');
        }

        $defaults = array('type' => 'form', 'username_input' => 'username', 'password_input' => 'password');
        foreach ($defaults as $k => $v) {
            if (!array_key_exists($k, $credentials)) {
                $credentials[$k] = $v;
            }
        }

        return $credentials;
    }

    public function getBackendLogger()
    {
        return trim($this->backendLogger);
    }

    /**
     * Faz a requisição, seja por Zend Http Client ou Consumindo Feed
     * Makes the request, either by Zend Http Client or consuming Feed
     */
    public function getCrawler($URI, $mode = 'GET', $type =  'html')
    {
        $this->logger(
            'created a Crawler for:'
                ."\n"
                . $URI
                ."\n"
            ,'info', 5);

        $this->requests++;
        if ($type == 'html') {
            try {
                $this->logger('Create instance of Goutte', 'debug', 5);
                $client = $this->goutte->request($mode, $URI);
                $this->logger('Goutte done', 'debug', 5);
            }
            catch(\Zend\Http\Client\Adapter\Exception\TimeoutException $e)
            {
                $this->logger('Goutte exception Timeout', 'debug', 3);
                $this->logger( 'faillure on create a crawler [' . $URI . ']', 'err');
            }

            //Error in request
            $this->logger(
                'Status Code: ['
                    . $this->getResponse()->getStatus() . ']', 'info', 4
            );
            if($this->getResponse()->getStatus() >= 400){
                throw new \Exception(
                    'Request with error: '
                        . $this->getResponse()->getStatus()
                        . " - " . $this->getResponseErrorMessage($client)
                );
            }
        } else {

            $this->logger('Create instance of Zend Feed Reader', 'info', 4);
            $client = $this->feedReader->request($URI);
        }

        return $client;
    }

    /**
     * Retorna o resumo de operações até o momento
     * para compatibilidade com versões anteriores
     */
    public function getResume()
    {
        return $this->getSummary();
    }

    /**
     * Retorna o resumo de operações até o momento
     * Returns a summary of the operations so far
     *
     * @return string
     */
    public function getSummary()
    {

        return "\n\n"
            . sprintf(
                $this->getSummaryTemplate(),
                $this->subscription,
                $this->getMemoryUsage(),
                $this->requests,
                $this->getTimeUsage('total'),
                $this->cached,
                $this->success,
                (isset($this->hyperlinks)) ? $this->hyperlinks : 0,
                $this->errors,
                $this->getBackendLogger()
            );
    }

    /**
     * Execute login on a webform
     *
     * @param array $credentials
     * @return bool status of login
     */
    public function loginForm(array $credentials)
    {

        foreach ($this->loginFormRequirements() as $r) {
            if (!array_key_exists($r, $credentials)) {
                throw new \Exception('Login on web form require ' . $r . ' attribute');
            }
        }

        $formUri = $this->subscription->getUriTarget();
        $this->addBackendLogger('Acessando *' . $formUri . '*');
        $this->logger('Get webform for '. $formUri);

        $crawler = $this->getClient()->request('GET', $formUri);

        if (!$crawler) {
            throw new \Exception('Login on web form require a instance of Crawler');
        }

        //Locate form
        $button = $this->loginButtonLocate($crawler, $credentials);

        $form = $button->form();

        //Fill inputs
        $values = array();
        foreach (array('username', 'password') as $k) {
            $input = $credentials[$k . '_input'];
            $values[$credentials[$k . '_input']] = $credentials[$k];
            $form[$credentials[$k . '_input']] = $credentials[$k];
            $this->addBackendLogger('Preenchendo o campo *'
                . $credentials[$k . '_input']
                . '* com o valor *'
                . $credentials[$k]
                . '*');

        }
        // submit the form
        $this->addBackendLogger('Login Submit');
        $crawler = $this->getClient()->submit($form);

        $crawler = $this->getClient()->request('GET', $formUri);
        //Check return
        $this->addBackendLogger('Testando a existência da frase: *' . $credentials['expected'] . '*');
        $responseText = $crawler->first()->text();

        if (false !== mb_stripos($responseText, $credentials['expected']))
        {
            //Successful
            $this->addBackendLogger('Login Successful');

            return true;
        } else {
            $this->addBackendLogger('Frase não encontrada');
        }

        //Failed
        return false;
    }

    public function loginFormRequirements()
    {
        return array('username', 'password', 'button', 'expected', 'password_input', 'username_input');
    }

    /**
     * @todo Clean html message
     */
    protected function getResponseErrorMessage($client)
    {
        return '';
    }

    protected function addBackendLogger($string)
    {
        $this->backendLogger .= $string . ".\n";
        $this->logger($string, 'info', $this->logger_level);
    }

    protected function resetBackendLogger()
    {
        $this->backendLogger = '';
    }

    protected function getCurrentUri()
    {
        return $this->getRequest()->getUri();
    }

    protected function getClient()
    {
        return $this->goutte;
    }

    protected function getBodyText()
    {
        return $this->getBody()->first()->text();
    }

    protected function getBody()
    {
        return $this->getResponse()->getContent();
    }

    protected function getRequest()
    {
        return $this->goutte->getRequest();
    }

    protected function getResponse()
    {
        return $this->goutte->getResponse();
    }

    protected function getSubscription()
    {
        return $this->subscription;
    }

   protected function getDomain()
    {
        return $this->getSubscription()->get('domain');
    }

    protected function getLinkTags()
    {
        return array(
            'subscription_' . $this->subscription['id'],
            'crawler',
            'processor'
        );
    }

    protected function getSummaryTemplate()
    {
        $template = <<<EOF
 ====================SUMMARY========================
    %s
    - Memory usage...........................%s Mb
    - Number of new requests.................%s 
    - Time total.............................%s Seg
    - Objects in cache.......................%s
    - Success................................%s
    - Hyperlinks.............................%s
    - Errors.................................%s

 ======================LOG==========================

%s

 ====================******=========================

EOF;

        return $template;
    }

    protected function getCookies($uri = null)
    {
        return $this->getClient()->getCookieJar()->allValues($uri);
    }

    protected function getCookie($uri, $key)
    {
        $cookies = $this->getCookies($uri);

        return array_key_exists($key, $cookies) ? $cookies[$key] : null;
    }

    protected function performLogin()
    {
        $auth = $this->subscription->getAuthInfo();

        if (empty($auth)) {
            //$this->addBackendLogger('Subscription without auth');

            return true;
        }

        $credentials = $this->getAuthCredentials($auth);

        //Try login
        switch ($credentials['type']) {
            default:
                $login = $this->loginForm($credentials);
        }

        if ($login) {

            return true;
        }

        //Error
        return false;
    }

    /**
     * @deprecated
     */
    protected function loginFormLocate($crawler, $credentials)
    {
        //try find by form name
        $elementName = 'form:contains("' . $credentials['contains'] . '")';
        $this->logger('Try locate form by element name ' . $elementName, 'info', $this->logger_level);
        $item = $crawler->filter($elementName);
        $this->logger('Itens located: #' . $item->count(), 'info', $this->logger_level);
        $form = $item->first()->first()->form();

        if (!$form instanceof Form) {
            throw new \Exception('Não localizou o Form');
        }

        $this->logger('Form Text: ' . $form->text(), 'info', $this->logger_level);

        return $form;
    }

    /**
     * Localiza o formulario para login
     * Find the login form
     */
    protected function loginButtonLocate($crawler, $credentials)
    {
        //try find by button name
        $button = $credentials['button'];
        $this->addBackendLogger('Localizando botão de formulário que contenha *' . $button . '*');
        $item = $crawler->selectButton($button);
        if (method_exists($item, 'count') && $item->count() > 0) {
            $this->debugger($item->count());
            $this->addBackendLogger('Tradicional button localized');
            goto done;
        }

        $elementName = '//input[contains(@src, "login")]';
        $this->logger('Try locate button by element name ' . $elementName, 'info', $this->logger_level);
        $item = $crawler->filterXPath($elementName);
        $this->logger('Itens located: #' . $item->count(), 'info', $this->logger_level);

        done:

        if ($item) {
            $this->addBackendLogger('Botão localizado');

            return $item;
        } else {
            $this->addBackendLogger('Botão não localizado');

            return false;
        }
    }

    /**
     * call on every new Subscription
     */
    protected function restart()
    {
        $this->goutte->restart();
        $this->resetBackendLogger();
        $this->start();
    }

    protected function start()
    {
        $this->requests = $this->errors = 0;
        $this->setTime('parcial');
        $this->pool = new Pool($this->transferDependency());
    }

    /**
     * Get Memory usage in MB
     * @return int
     **/
    protected function getMemoryUsage()
    {
        return round((\memory_get_usage(true)/1024) / 1024);
    }

    /**
     * @return int
     */
    protected function getTimeUsage($type = 'total')
    {
        return round(microtime(true) - $this->time[$type]);
    }

    /**
     * Checl if memory or requests is reached
     */
    protected function checkLimit()
    {
        if ($this->limitReached) {
            return false;
        }

        $this->logger(
            'Current memory usage:'
            . $this->getMemoryUsage()
            . 'Mb', 'info',
            5
        );

        if (
            $this->getMemoryUsage() > $this->getConfig('memory_limit', 75)
        ){
            $this->logger('Limit of memory reached', 'err');
            $this->limitReached = true;
           return false;
        }
        if(
            $this->requests >= $this->getConfig(
                'requests_limit',
                isset($_SERVER['HTTP_HOST']) ? 15 : 60
            )
        ){
            $this->limitReached = true;
            $this->logger('Limit of requests reached', 'info', 1);
            return false;
        }
        return true;
    }

    protected function setTime($type = 'total')
    {
        $this->time[$type] = microtime(true);
    }
}
