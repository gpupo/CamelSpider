<?php 

namespace CamelSpider\Entity;

use Doctrine\Common\Collections\ArrayCollection;

abstract class AbstractSubscription extends ArrayCollection implements InterfaceSubscription
{

    public function getId()
    {
        return $this->get('id');
    }

    public function getDomain()
    {
        return $this->get('domain');
    }
    public function getHref()
    {
        return $this->get('href');
    }

    public function getFilters()
    {
        return $this->get('filters');
    }
    
    public function getRecursive()
    {
        return $this->get('recursive');
    }

    
    public function getLink($sha1)
    {
        //make somethin cool with your DB!
        return false;
    }


}



