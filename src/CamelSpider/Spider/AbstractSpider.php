<?php

namespace CamelSpider\Spider;

use CamelSpider\Entity\AbstractSpiderEgg;

abstract class AbstractSpider extends AbstractSpiderEgg
{

    protected $timeStart;

    protected $elements;

    protected $requests = 0;

    protected $cached = 0;

    protected $errors = 0;

    protected $subscription;

    protected $timeParcial;

    protected $goutte;



    public function getCrawler($URI, $mode = 'GET')
    {

        $this->logger( 'created a Crawler for [' . $URI . ']');

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
        return $this->goutte->getResponse()->getContent();
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
        return $this->subscription->get('domain');
    }

    protected function getLinkTags()
    {
        return array(
            'subscription_' . $this->subscription['id'],
            'crawler',
            'processor'
        );
    }


    /**
     * return memory in MB
     **/
    protected function getMemoryUsage()
    {
        return round((\memory_get_usage()/1024) / 1024);
    }

    protected function getTimeUsage()
    {
        return round(microtime(true) - $this->timeParcial);
    }

    protected function checkLimit()
    {
        $this->logger('Current memory usage:' . $this->getMemoryUsage() . 'Mb');

        if($this->getMemoryUsage() >= $this->getConfig('memory_limit', 80)){
           $this->logger('Limit of memory reached', 'err');
           return false;
        }
        if($this->requests >= $this->getConfig('requests_limit', 300)){
            //throw new \Exception ('Limit reached');
            $this->logger('Limit of requests reached', 'err');
            return false;
        }
        $this->requests++;
        return true;
    }



}
