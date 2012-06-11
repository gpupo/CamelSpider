<?php

namespace CamelSpider\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Used to create fake Subscriptions quickly, for testing
 */
class FactorySubscription
{
    public static function build(array $array)
    {
        return new Subscription($array);
    }

    public static function buildFromDomain($domain, array $filters = null)
    {
        if (is_null($filters))
            $filters = array('contain' => 'rock', 'notContain' => 'polca');

        $array = array(
            'domain'      =>   $domain,
            'href'        =>   'http://'. $domain . '/',
            'max_depth'   =>   2,
            'filters'     =>   $filters,
            'id'          =>   sha1($domain)
        );

        return self::build($array);
    }

    public static function buildCollectionFromDomain(array $array)
    {
        $collection = new ArrayCollection();
        foreach($array as $domain)
        {
            $collection->add(self::buildFromDomain($domain));
        }
        return $collection;
    }
}
