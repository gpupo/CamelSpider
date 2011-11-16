<?php

namespace CamelSpider\Spider;

use Respect\Validation\Validator as v,
    CamelSpider\Entity\InterfaceLink,
    Zend\Uri\Uri;

class SpiderAsserts
{

    public static function containKeywords($txt, $keywords = null, $ifNull = true)
    {
        if (!is_array($keywords) || count($keywords) < 1) {
            return $ifNull; // Subscription not contain filter for keywords
        }
        foreach ($keywords as $keyword) {
            if (v::contains($keyword)->validate($txt)) {
                return true;
            }
        }

        return false;
    }

    public static function isDocumentHref($href)
    {
        if(
            empty($href)                            ||
            stripos($href, 'mail') !== false        ||
            substr($href, 0,10) == 'javascript'     ||
            substr($href, 0, 1) == '#'
        ) {
            return false;
        }
        foreach (array('%20', '=') as $c) {
            if (stripos($href, $c . 'http://') !== false) {
                return false;
            }
        }

        $zendUri = new Uri($href);
        if ($zendUri->isValid()) {
            return true;
        }

        return false;
    }


    public static function isDocumentLink(InterfaceLink $link)
    {
        return self::isDocumentHref($link->getHref());
    }

}

