<?php

namespace CamelSpider\Entity;
use Doctrine\Common\Collections\ArrayCollection;


/** 
 * Este é um ovo :)
 * Ele abstrai métodos reusáveis entre a maior
 * parte dos objetos do projeto
 */


class AbstractSpiderEgg extends ArrayCollection
{
    protected $config;
    protected $logger;
    protected $name;

    public function __construct(array $array, array $config = NULL)
    {
        if($config)
        {
            $this->set('config', new DoctineArrayCollection($config));
        }

        parent::__construct($array);
    }

    protected function getConfig($key, $defaultValue = NULL)
    {
        if($config = $this->config->get($key)){
            return $config;
        }
        return $defaultValue;
    }

    protected function logger($string, $type = 'info')
    {
        if($this->logger){
            return $this->logger->$type('#CamelSpider ' . $name . ':'  . $string);
        }
    }

}

