<?php

namespace CamelSpider\Spider;

use CamelSpider\Tools\IdeiasLang;

/**
 * Helper for Strings
 */
class SpiderTxt
{

    public static function diffPercentage($a, $b)
    {
        $percentage = IdeiasLang::iDiff($a, $b);
        var_dump($percentage);
        return $percentage;
    }

}

