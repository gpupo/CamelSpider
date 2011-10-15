<?php

namespace CamelSpider\Spider;

use Respect\Validation\Validator as v,
    CamelSpider\Entity\InterfaceLink,
    Zend\Uri\Uri;

class SpiderAsserts 
{

    public static function containKeywords($txt, array $keywords = null)
    {
        if(!$keywords) {
            return true; // Subscription not contain filter for keywords
        }
        foreach($keywords as $keyword){
            if(v::contains($keyword)->validate($txt)) {
                return true;
            }
        }

        return false;
    }

    public static function isDocumentHref($href)
    {
        if(
            stripos($href, 'mail') == true ||
            empty($href) ||
            substr($href, 0,10) == 'javascript' ||
            substr($href, 0, 1) == '#'
        ) {
            return false;
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

