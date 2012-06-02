<?php

namespace CamelSpider\Entity;

use CamelSpider\Entity\AbstractSpiderEgg,
    CamelSpider\Entity\InterfaceSubscription,
    CamelSpider\Entity\InterfaceLink;

/**
 * Armazena a fila de Links processados
 *
 * @package     CamelSpider
 * @subpackage  Entity
 * @author      Gilmar Pupo <g@g1mr.com>
 **/
class Pool extends AbstractSpiderEgg
{
    protected $name = 'Pool';

    public function __construct($dependency = null)
    {
        if ($dependency) {
            foreach (array('logger', 'cache') as $k) {
                if (isset($dependency[$k])) {
                    $this->$k = $dependency[$k];
                }
            }
        }
        $config = isset($dependency['config']) ? $dependency['config'] : null;
        parent::__construct(array('relevancy'=>0), $config);
    }

    /**
     * Reduce for only Links waiting process
     *
     * @return array
     */
    protected function filterWaiting()
    {
        $a = array();

        foreach ($this->toArray() as $link) {
            if (!$link instanceof InterfaceSubscription && $link instanceof InterfaceLink && $link->isWaiting()) {
                $a[] = $link;
            }
        }

        return $a;
    }

    /**
     * Reduce for only Links with process finished
     *
     * @return array
     */
    public function getPackage()
    {
        $a = array();

        foreach ($this->toArray() as $link) {
            if ($link instanceof InterfaceLink && !$link instanceof InterfaceSubscription && $link->isDone()) {
                $a[] = $link;
            }
        }

        return $a;
    }


    /**
     * @return array
     */
    public function getPool($mode)
    {
        $pool =  $this->filterWaiting();
        if(count($pool) < 1)
        {
            $this->logger('Pool empty on the ' . $mode, 'info', 5);
            return false;
        }
        $this->logger('Pool count:' . count($pool), 'info', 1);

        return $pool;
    }

    /**
     * Check if link is processed
     */
    public function isDone(InterfaceLink $link)
    {
        if ($cache = $this->cache->getObject($link->getId())) {
            return $cache->isDone();
        } else {
            return false;
        }
    }

    private function _save(InterfaceLink $link)
    {
        $this->set($link->getId('string'), $link);
    }

    /**
     * Adiciona/subscreve elemento na fila
     */
    public function save(InterfaceLink $link)
    {

        if ($link instanceof InterfaceSubscription) {
            return false;
        }

        if($link->isDone()){
            $this->cache->save($link->getId('string'), $link->toPackage());
        }

        $this->_save($link->toMinimal());

        return $this;
    }

    public function errLink(InterfaceLink $link, $cause = 'undefined')
    {
        $link->setStatus(3);
        $this->_save($link);
        $this->logger(
            $link->get('href')
            ."\n"
            .' marked with error.'
            .'Cause: ' . $cause
            ."\n");
        $this->errors++;
    }
}