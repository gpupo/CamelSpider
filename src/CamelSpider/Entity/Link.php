<?php

namespace CamelSpider\Entity;
use Doctrine\Common\Collections\ArrayCollection,
    CamelSpider\Entity\Document,
    CamelSpider\Entity\InterfaceLink;

class Link extends ArrayCollection implements InterfaceLink
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
    public function setDocument($response, $subscription, array $dependency = NULL)
    {
        $this->set('document', new Document($response, $subscription, $dependency));
    }
    public function getDocument()
    {
        return $this->get('document');
    }

    /**
     * reduce memory usage
     */
    public function toMinimal()
    {
        $this->removeElement('document');
        $this->set('document', $this->getDocument()->toArray());

        return $this;
    }

    public function toArray()
    {
        return $this->toMinimal()->toArray();
    }

}
