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
    protected $cache;
    protected $name;

    public function __construct(array $array, array $config = NULL)
    {
        if($config)
        {
            $this->set('config', new DoctineArrayCollection($config));
        }

        parent::__construct($array);
    }

    protected function transferDependency()
    {
        return array(
            'logger' => $this->logger,
            'cache'  => $this->cache,
            'config' => $this->config
        );
    }

    protected function getConfig($key, $defaultValue = NULL)
    {
        if($this->config instanceof ArrayCollection && $config = $this->config->get($key)){
            return $config;
        }
        return $defaultValue;
    }

    /**
     * @todo Lidar com níveis da configuração de cada componente
     */
    protected function logger($string, $type = 'info', $level = 1)
    {
        if($this->logger){
            return $this->logger->$type('#CamelSpider ' . $this->name . ':'  . $string);
        }
    }

}

