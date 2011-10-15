<?php

namespace CamelSpider\Spider;
use Doctrine\Common\Collections\ArrayCollection;

class SpiderElements extends ArrayCollection 
{
    public function getPool()
    {
       return $this->filter(function ($e) { return $e->isWaiting();}); 
    }    
}
