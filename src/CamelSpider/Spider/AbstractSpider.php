<?php

namespace CamelSpider\Spider;

use CamelSpider\Entity\AbstractSpiderEgg,
    CamelSpider\Entity\Pool;

abstract class AbstractSpider extends AbstractSpiderEgg
{
    protected $name = 'Spider';

    protected $time = array('total' => 0, 'parcial' => 0);

    protected $pool;

    protected $hiperlinks = 0;

    protected $requests = 0;

    protected $cached = 0;

    protected $errors = 0;

    protected $success = 0;

    protected $subscription;

    protected $timeParcial;

    protected $goutte;

    protected $limitReached = false;

    public function getCrawler($URI, $mode = 'GET', $type =  'html')
    {

        $this->logger(
            'created a Crawler for:'
            ."\n"
            . $URI
            ."\n"
        ,'info', 3);

        $this->requests++;
        if ($type == 'html') {
            $this->logger('Create instance of Goutte', 'debug', 3);
            try {
                $client = $this->goutte->request($mode, $URI);
            }
            catch(\Zend\Http\Client\Adapter\Exception\TimeoutException $e)
            {
                $this->logger( 'faillure on create a crawler [' . $URI . ']', 'err');
            }

            //Error in request
            $this->logger('Status Code: [' . $this->getResponse()->getStatus() . ']', 'info', 3);
            if($this->getResponse()->getStatus() >= 400){
                throw new \Exception('Request with error: ' . $this->getResponse()->getStatus() 
                    . " - " . $client->text()
                );
            }
        } else {

            $this->logger('Create instance of Zend Feed Reader');
            $client = $this->feedReader->request($URI);
        }

        return $client;
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

    protected function getResumeTemplate()
    {
        $template = <<<EOF
 ====================RESUME=========================
    %s
    - Memory usage...........................%s Mb
    - Number of new requests.................%s 
    - Time total.............................%s Seg
    - Objects in cache.......................%s
    - Success................................%s
    - Hyperlinks.............................%s
    - Errors.................................%s

EOF;

        return $template;
    }

    /**
     * Retorna o resumo de operações até o momento
     * @return string
     */
    public function getResume()
    {

        return "\n\n"
            . sprintf(
                $this->getResumeTemplate(),
                $this->subscription,
                $this->getMemoryUsage(),
                $this->requests,
                $this->getTimeUsage('total'),
                $this->cached,
                $this->success,
                (isset($this->hyperlinks)) ? $this->hyperlinks : 0,
                $this->errors
            );
    }


    public function debug()
    {
        echo $this->getResume();
    }

    protected function performLogin()
    {
        $auth = $this->subscription->getAuthInfo();

        if (empty($auth)) {
            $this->logger('Subscription without auth');

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

    public function loginFormRequirements()
    {
        return array('username', 'password', 'button', 'expected', 'password_input', 'username_input');
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

        $this->logger('Get webform for '. $formUri);
        $crawler = $this->getCrawler($formUri, 'GET');

        if (!$crawler) {
            throw new \Exception('Login on web form require a instance of Crawler');
        }

        //Locate form
        $form = $crawler->selectButton($credentials['button'])->form();
        //Fill inputs
        foreach (array('username', 'password') as $k) {
            $form[$credentials[$k . '_input']] = $credentials[$k];
        }
        // submit the form
        $crawler = $client->submit($form);

        //Check return
        if ($crawler->filter('contains("' . $credentials['expected'] . '")')->count() > 0)
        {
            //Successful
            return true;
        }

        //Failed
        return false;

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

    protected function restart()
    {
        $this->goutte->restart();
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

    protected function checkLimit()
    {
        if($this->limitReached){
            return false;
        }

        $this->logger('Current memory usage:' . $this->getMemoryUsage() . 'Mb', 'info', 5);

        if($this->getMemoryUsage() > $this->getConfig('memory_limit', 50)){
            $this->logger('Limit of memory reached', 'err');
            $this->limitReached = true;
           return false;
        }
        if($this->requests >= $this->getConfig('requests_limit', 150)){
            //throw new \Exception ('Limit reached');
            $this->limitReached = true;
            $this->logger('Limit of requests reached', 'err');
            return false;
        }
        return true;
    }

    protected function setTime($type = 'total')
    {
        $this->time[$type] = microtime(true);
    }
}
