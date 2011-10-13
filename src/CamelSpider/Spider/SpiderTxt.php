<?php

namespace CamelSpider\Spider;
/**
 * Helper for Strings
 */
class SpiderTxt
{
    public static function diff($a, $b)
    {
     
         $d = new Text_Diff('auto', array('teste', 'tes'));

         var_dump($d);
      
         return false;
    }

    public static function diffPercentage($a, $b)
    {
        return 100;
    }


