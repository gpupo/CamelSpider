<?php

namespace CamelSpider\Spider;

use CamelSpider\Entity\AbstractSpiderEgg;

abstract class AbstractSpider extends AbstractSpiderEgg
{

    protected $timeStart;

    protected $timeParcial;


    protected function getBody()
    {
        return $this->crawler->filter('body');
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


}
