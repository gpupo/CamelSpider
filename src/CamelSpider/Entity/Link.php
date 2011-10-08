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
		return  ($this->indexOf('response')) ? false : true;
	}
}
