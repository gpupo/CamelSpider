<?php

namespace CamelSpider\Entity;

interface InterfaceSubscription extends InterfaceLink{

    public function getDomain();
    public function getHref();
    public function getFilters();
    public function getRecursive();
   // public function getId();
    
    //Return Object by Sha1 of url
    public function getLink($sha1);

}
