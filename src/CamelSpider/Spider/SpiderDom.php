<?php

namespace CamelSpider\Spider;

/**
 * Methods for DOMElements 
 *
 * @package     CamelSpider
 * @subpackage  Spider
 * @author      Gilmar Pupo <g@g1mr.com>
 * @see         http://www.php.net/manual/en/class.domelement.php
 */

class SpiderDom
{

    public static function toHtml(\DOMElement $node)
    {
        return $node->ownerDocument->saveXML($node);
    }
    /**
     * Convert HTML to plain text
     */
    public static function toText(\DOMElement $node)
    {
    }
    public static function saveHtmlToFile(\DOMElement $node, $file)
    {
        return $node->ownerDocument->saveHTMLFile($file);
    }


    public static function countInnerTags(\DOMElement $node, $tag)
    {
        $a = $node->getElementsByTagName($tag);
        return $a->length;
    }
  
}



