<?php

namespace CamelSpider\Spider;

use Respect\Validation\Validator as v;

class SpiderAsserts 
{

    public static function respect()
    {
        $validUsername = v::alnum()
            ->noWhitespace()
            ->length(1,15);
        return $validUsername->validate('alganet');
    }

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
}

