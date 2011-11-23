<?php

namespace CamelSpider\Entity;
use Doctrine\Common\Collections\ArrayCollection,
    CamelSpider\Entity\Document,
    CamelSpider\Entity\InterfaceLink;

class Link extends ArrayCollection implements InterfaceLink
{
    public function __construct($node = null)
    {
        $link = array();

        if (!is_null($node) && $node instanceof \DOMElement) {
            $link = array(
                'href' => $node->getAttribute('href'),
            );
        } elseif (is_string($node)) {
            $link['href'] = $node;
        }

        $link['status'] = 0;
        parent::__construct($link);
    }

    public function setStatus($x)
    {
        return $this->set('status', $x);
    }

    /**
     * Gera o hash para armazenar em cache
     **/
    public function getId($mode = null)
    {
        return sha1($this->get('href'));
    }

    public function getHref()
    {
        return $this->get('href');
    }

    public function isWaiting()
    {
        return  ($this->get('status') === 0) ? true : false;
    }
    public function isDone()
    {
        return  ($this->get('status') === 1) ? true : false;
    }

    public function setDocument($uri, $response, $subscription, array $dependency = NULL)
    {
        $this->set('document', new Document($uri, $response, $subscription, $dependency));
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
        if ($this->getDocument() instanceof Document) {
            $this->removeElement('document');
        }

        return $this;
    }

    public function toPackage()
    {
        if ($this->getDocument() instanceof Document) {
            $this->set('document', $this->getDocument()->toPackage());
        }

        return $this;
    }


    public function toArray()
    {
        return $this->toMinimal()->toArray();
    }

}
