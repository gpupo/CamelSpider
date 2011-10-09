<?php

namespace CamelSpider\Entity;
use Doctrine\Common\Collections\ArrayCollection;

class Link extends ArrayCollection 
{
	
	
	public function __construct($node = NULL)
	{
		$link = array();
		
		if($node){
			$link = array(
				'href' => $node->getAttribute('href'),
				);
        }
        $link['status'] = 0;
		parent::__construct($link);
    }

    /**
     * Gera o hash para armazenar em cache
     **/
    public function getId()
    {
        return sha1($this->get('href'));
    }

    public function isWaiting()
	{
		return  ($this->get('status') === 1) ? false : true;
    }
    public function isDone()
    {
		return  ($this->get('status') === 1) ? true : false;
    }

    /**
     * reduce memory usage
     */
    public function getMinimal()
    {
        $this->remove('response');
        return $this;
    }
}
