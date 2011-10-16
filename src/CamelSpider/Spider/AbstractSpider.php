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

    public function getCrawler($URI, $mode = 'GET')
    {

        $this->logger( 'created a Crawler for [' . $URI . ']');
        $this->requests++;
        try {
            $client = $this->goutte->request($mode, $URI);
        }
        catch(\Zend\Http\Client\Adapter\Exception\TimeoutException $e)
        {
            $this->logger( 'faillure on create a crawler [' . $URI . ']', 'err');	
        }

        //Error in request
        $this->logger('Status Code: [' . $this->getResponse()->getStatus() . ']');
        if($this->getResponse()->getStatus() >= 400){
            throw new \Exception('Request with error: ' . $this->getResponse()->getStatus() 
                . " - " . $client->text()
            );
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
                $this->hyperlinks,
                $this->errors
            );
    }


    public function debug()
    {
        echo $this->getResume();
    }

    protected function performLogin()
    {
        /**
         * @todo Verifica se a assinatura precisa login
         * Usa o Goutte para logar
         * Verifica o tipo de login requerido
         */
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

        if($this->getMemoryUsage() > $this->getConfig('memory_limit', 80)){
            $this->logger('Limit of memory reached', 'err');
            $this->limitReached = true;
           return false;
        }
        if($this->requests >= $this->getConfig('requests_limit', 300)){
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
