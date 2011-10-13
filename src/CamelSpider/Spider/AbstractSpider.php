<?php

namespace CamelSpider\Spider;

abstract class AbstractSpider
{

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
