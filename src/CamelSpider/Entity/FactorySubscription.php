<?php 

namespace CamelSpider\Entity;

use Doctrine\Common\Collections\ArrayCollection;


class FactorySubscription
{

    public static function build(array $array)
    {
        return new Subscription($array);
    }
    public static function buildFromDomain($domain)
    {
        $array = array(
            'domain'      =>   $domain,
            'href'        =>   'http://'. $domain . '/',
            'max_depth'   =>   1,
            'filters'     =>   array('contain' => 'tecnologia', 'notContain' => 'agrícola'),
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

