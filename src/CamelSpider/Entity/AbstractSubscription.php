<?php 

namespace CamelSpider\Entity;

use Doctrine\Common\Collections\ArrayCollection,
    CamelSpider\Entity\InterfaceLink;

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
    
    public function getMaxDepth()
    {
        return $this->get('max_depth');
    }

    
    public function getLink($sha1)
    {
        //make somethin cool with your DB!
        return false;
    }
    public function isDone()
    {
        return true;
    }
    public function isWaiting()
    {
        return false;
    }

    public function getMinimal()
    {
        return $this;
    }


}



