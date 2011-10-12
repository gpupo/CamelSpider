<?php

namespace CamelSpider\Spider;

class SpiderDom
{

    public static function toHtml(\DOMElement $node)
    {
        return $node->ownerDocument->saveXML($node);
    }

    public static function countInnerTags(\DOMElement $node, $tag)
    {
        $a = $node->getElementsByTagName($tag);
        return $a->length;
    }
  
}



