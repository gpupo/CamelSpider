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
    const rootTag = 'rootTag';

    public static $stripedTags = array(
        'b',
        'span',
        'a',
        'div',
        'li',
        'ul',
        'div',
        'dl',
        'ol',
        'p',
        'td',
        'tr',
        'table',
        'body',
        'html'
    );

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

    public static function countInnerTags(\DOMElement $node, $tag)
    {
        $a = $node->getElementsByTagName($tag);

        return $a->length;
    }

    public static function getGreater(\DOMElement $a, \DOMElement $b = NULL, $options = array())
    {
        if(!$b)
            return $a;

        if(self::textLen($a) < self::textLen($b))
            return $b;

        return $a;
   }

    /**
     * Process node and generate his html
     */
    public static function getNodeHtml(\DOMDocument $doc)
    {
        $html = trim(str_replace('<?xml version="1.0"?>', '', $doc->saveXML()));

        if (stripos($html,'<html') === false) {
            $html = str_replace(self::rootTag, 'html', $html);
        } else {
            $html = str_replace(
                array('<' . self::rootTag . '>', '</'. self::rootTag . '>'),
                '',
                $html
            );
        }

        return $html;
    }

    /**
     * Convert html to DomElement
     */
    public static function htmlToDomElement($html)
    {
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($html);
        $element = $doc->documentElement;

        if (!$element instanceof \DomElement) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                $this->logger($error, 'err', 3);
            }
            libxml_clear_errors();
            throw new \UnexpectedValueException('DomElement expected');
        }

        return $element;
    }

    public static function htmlToIntro($html,$length,$end='',$encoding='UTF-8')
    {
        $string = static::strip_tags($html);
        $len = mb_strlen($string,$encoding);
        if ($len <= $length) {
            return $string;
        } else {
            $return = mb_substr($string,0,$length,$encoding);
            return (preg_match('/^(.*[^\s])\s+[^\s]*$/', $return, $matches) ? $matches[1] : $return).$end;
        }
    }

    /**
     * Transform html to plain text
     *
     * @return string $text
     */
    public static function htmlToText($html)
    {
        $text = static::strip_tags($html);
        return $text;
    }

    public static function normalizeDocument(\DOMNode $node)
    {
        $rootTag = 'rootTag';

        if ($node instanceof \DOMDocument) {
            $node = $node->documentElement;
        }

        $doc = new \DOMDocument;
        $doc->loadXML('<' . $rootTag . '></'. $rootTag . '>');
        $docNode = $doc->importNode($node, true);
        $doc->documentElement->appendChild($docNode);

        return $doc;
    }

    /**
     * @deprecated
     */
    public static function oldToHtml(\DOMElement $node)
    {
        return $node->ownerDocument->saveXML($node);
    }

    /**
     * Remove attributes from hell!
     *
     * @param string $content is a html
     * @param array $attrs is a list of attributes to clean
     */
    public static function removeDirtyAttrs($content, $attrs = null)
    {
        if (is_null($attrs)) {
            $attrs = array(
                'oncontextmenu',
                'ondragstart',
                'onselectstart',
                'onselect',
                'oncopy',
                'onbeforecopy',
                'onclick',
                'onload',
                'onblur',
                'onfocus',
                'onchange',
                'onsubmit',
                'style',
                'class',

            );
        }

        foreach ($attrs  as $a) {
            $content = preg_replace("/" . $a . '=\s*"[^\"]*\"/i', '', $content);
        }

        $i = 0;
        while ($i < 10) {
            $content = str_replace('  ', ' ',  $content);
            $content = str_replace(' >', '>', $content);
            $content = str_replace(PHP_EOL . '>', '>', $content);
            $i++;
        }

        return $content;
    }

    public static function removeTag($tag, $content)
    {
        foreach (array(
                     '/<'. $tag . '.*?<\/' . $tag . '>/is',
                     '/<'. $tag . '.*?\/>/is'
                 ) as $expr) {
            $content = preg_replace($expr, '', $content);
        }

        return $content;
    }

    public static function removeTrashBlock($content)
    {
        foreach (array('iframe', 'script', 'style', 'noscript') as $tag) {
            $content = static::removeTag($tag, $content);
        }

        return $content;
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

    public static function strip_tags($content, $allow = null)
    {
        $content = str_replace('&nbsp;', '', strip_tags($content, $allow));
        preg_match_all("/<([^>]+)>/i",$allow,$tags,PREG_PATTERN_ORDER);

        if (!$allow) {
            $allow = static::$stripedTags;
        }

        foreach (array_merge($tags[1],static::$stripedTags)  as $tag){
            $content = preg_replace("/<".$tag."[^>]*>/i","<".$tag.">",$content);
        }
        //remove wrong tags
        foreach (array_merge($tags[1],static::$stripedTags)  as $tag){
            $content = str_ireplace("<".$tag."><".$tag.">", "<".$tag.">", $content);
        }
        foreach (array('img') as $tag) {
            $content = str_ireplace("<".$tag.">", '', $content);
        }

        $content = trim($content);

        return $content;
    }

    public static function substr_count($node, $substring)
    {
        return substr_count(self::toText($node), $substring);
    }

    /**
     * Calcula a quantidade de texto que um DomElement possui.
     *
     * @param DomElement $node
     * @return int Minor count
     */
    public static function textLen($node)
    {
        $node = static::normalizeDomNode($node);

        $mode_1 = mb_strlen(self::toText($node, 'textContent'));

        $mode_2 = mb_strlen(self::toText($node, 'clean'));

        return ($mode_1 < $mode_2) ? $mode_1 : $mode_2;
    }

    public static function toCleanHtml(\DOMNode $node)
    {
        //remove <head>
        foreach (array('script', 'head') as $tag) {
            $node = static::removeChild($node, $tag);
        }

        return static::removeDirtyAttrs(static::removeTrashBlock(static::toHtml($node)));
    }

    /**
     * Save an isolated node as html
     *
     * @return string HTML
     */
    public static function toHtml(\DOMNode $node)
    {
        $doc = static::normalizeDocument($node);

        return static::getNodeHtml($doc);
    }

    /**
     * Convert HTML to plain text
     * @see http://www.php.net/manual/en/class.domtext.php
     */
    public static function toText(\DOMNode $node, $mode = 'textContent')
    {
        if ($mode = 'clean') {
            $html = static::toCleanHtml($node);
            $node = static::htmlToDomElement($html);
        }
        $text = trim($node->textContent);

        return $text;
    }

    protected static function normalizeDomNode(\DomNode $node)
    {
        if ($node instanceof \DomDocument) {
            $node = $node->documentElement;
        }

        return $node;
    }

    protected static function removeChild(\DOMNode $node, $tag)
    {
        try {
            $list =  $node->getElementsByTagname($tag);
            foreach ($list as $element) {
                $element->parentNode->removeChild($element);
            }
        } catch (\DOMException $e) {
            \error_log('Element <'
                . $tag
                . '> not found: '
                . $e->getMessage()
                . "\nHTML:"
                . static::toHtml($node) , 4);
        }

        return $node;
    }
}



