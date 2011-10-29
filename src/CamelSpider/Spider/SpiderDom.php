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


    /**
     * Verifica se um DomElement é candidato a ser o container
     * de conteúdo
     * @todo utilizar configuracões injetadas
     */
    public static function containerCandidate($node)
    {
        if(self::textLen($node) < 500)
            return false;

        if(self::substr_count($node, '              ') > 5)
            return false;

        if(self::substr_count($node, '"') > 30)
            return false;

        if(self::countInnerTags($node, 'a') > 5)
            return false;

        if(self::countInnerTags($node, 'javascript') > 2)
            return false;

        return true;
    }

    public static function getGreater(\DOMElement $a, \DOMElement $b = NULL)
    {

        if(!$b)
            return $a;

        if(self::textLen($a) < self::textLen($b))
            return $b;

        return $a;
   }

    /**
     * @deprecated
     */
    public static function oldToHtml(\DOMElement $node)
    {
        return $node->ownerDocument->saveXML($node);
    }

    /**
     * Save an isolated node as html
     *
     * @return string HTML
     */
    public static function toHtml(\DOMNode $node)
    {
        $rootTag = 'rootTag';

        if ($node instanceof \DOMDocument) {
            $node = $node->documentElement;
        }

        $doc = new \DOMDocument;
        $doc->loadXML('<' . $rootTag . '></'. $rootTag . '>');
        $docNode = $doc->importNode($node, true);
        $doc->documentElement->appendChild($docNode);

        $html = trim(str_replace('<?xml version="1.0"?>', '', $doc->saveXML()));
        if (stripos($html,'<html>') === false) {
            $html = str_replace($rootTag, 'html', $html);
        } else {
            $html = str_replace(array('<' . $rootTag . '>', '</'. $rootTag . '>'), '', $html);
        }

        return $html;
    }

    /**
     * Convert HTML to plain text
     * @see http://www.php.net/manual/en/class.domtext.php
     */
    public static function toText(\DOMElement $node)
    {
        return trim($node->textContent);
    }

    public static function textLen($node)
    {
        return strlen(self::toText($node));
    }

    public static function substr_count($node, $substring)
    {
        return substr_count(self::toText($node), $substring);
    }

    public static function saveDomToHtmlFile(\DOMElement $node, $file)
    {
        return $node->ownerDocument->saveHTMLFile($file);
    }
    public static function saveHtmlToFile(\DOMElement $node, $file)
    {
    }

    public static function saveTxtToFile(\DOMElement $node, $file, $title = NULL)
    {
        return file_put_contents($file, $title . self::toText($node));
    }


    public static function countInnerTags(\DOMElement $node, $tag)
    {
        $a = $node->getElementsByTagName($tag);
        return $a->length;
    }
  
}



