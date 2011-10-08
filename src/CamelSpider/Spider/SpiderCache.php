<?php

namespace CamelSpider\Spider;
use Doctrine\Common\Collections\ArrayCollection;

class SpiderCache extends ArrayCollection 
{
    public function getPool()
    {
       return $this->filter(function ($e) { return $e->isWaiting();}); 
    }    
}
