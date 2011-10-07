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
}