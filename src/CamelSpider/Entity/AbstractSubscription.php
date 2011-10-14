<?php 

namespace CamelSpider\Entity;

use Doctrine\Common\Collections\ArrayCollection,
    CamelSpider\Entity\InterfaceLink,
    CamelSpider\Entity\Link;

abstract class AbstractSubscription extends ArrayCollection implements InterfaceSubscription
{

    public function getId()
    {
        return $this->get('id');
    }

    public function getDomain()
    {
        if(strpos(',', $this->get('domain')) === true){
            return explode(',', $this->get('domain'));
        }
        else
        {
            return array($this->get('domain'));
        }
    }
    public function getHref()
    {
        return $this->get('href');
    }

    public function getFilters()
    {
        return $this->get('filters');
    }

    public function getDomainString()
    {
        return implode(',', $this->getDomain());
    }
    public function __toString()
    {
        return $this->getDomainString();
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
    protected function inDomain($str)
    {
        foreach($this->getDomain() as $domain)
        {
            if(stripos($str, $domain))
            {
                    return true;
            }
        }
    }

    public function insideScope(Link $link)
    {
        if(
            substr($link->get('href'), 0, 4) == 'http' && 
            !$this->inDomain($link->get('href'))
		){
            return false;
        }
        return true;
    }

}



