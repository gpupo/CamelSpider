<?php

namespace CamelSpider\Entity;

use CamelSpider\Entity\AbstractSpiderEgg,
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
    protected $name = 'Document';

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
     * @return Pool reduced
     */
    public function getWaiting()
    {
        return $this->filter(
            function ($link) {

                if ($link instanceof Link) {
                    return $link->isWaiting();
                }

                return false;
            }
        );
    }

    public function getPool($mode)
    {
        $pool =  $this->getWaiting();
        if($pool->count() < 1)
        {
            $this->logger('Pool empty on the ' . $mode, 'info', 5);
            return false;
        }
        $this->logger('Pool count:' . $pool->count(), 'info', 5);
        return $pool;
    }

    private function _save(InterfaceLink $link)
    {
        $this->set($link->getId(), $link);
    }

    /**
     * Adiciona/subscreve elemento na fila
     */
    public function save(InterfaceLink $link)
    {
        if($link->isDone()){
            $this->cache->save($link->getId(), $link);
        }

        $this->_save($link->toMinimal());

        return $this;
    }

    public function errLink($link, $cause = 'undefined')
    {
        $link->set('status', 3);
        $this->_save($link);
        $this->logger($link->get('href')  . ' marked with error. Cause [' . $cause . ']');
        $this->errors++;
    }


}
