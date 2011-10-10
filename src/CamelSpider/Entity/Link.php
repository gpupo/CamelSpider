<?php

namespace CamelSpider\Entity;
use Doctrine\Common\Collections\ArrayCollection,
    CamelSpider\Entity\Document;

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
		return  ($this->get('status') === 0) ? true : false;
    }
    public function isDone()
    {
		return  ($this->get('status') === 1) ? true : false;
    }
    public function setDocument($response, $subscription, $logger = NULL)
    {
        $this->set('document', new Document($response, $subscription, $logger));
    }
    public function getDocument()
    {
        return $this->get('document');
    }

    /**
     * reduce memory usage
     */
    public function getMinimal()
    {
        $this->removeElement('document');
        return $this;
    }
}
