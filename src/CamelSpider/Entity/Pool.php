<?php

namespace CamelSpider\Spider;

use CamelSpider\Entity\AbstractSpiderEgg;

/**
 * Armazena a fila de Links processados
 *
 * @package     CamelSpider
 * @subpackage  Entity
 * @author      Gilmar Pupo <g@g1mr.com>
 **/
class Pool extends AbstractSpiderEgg
{
    public function getPool()
    {
       return $this->filter(function ($e) { return $e->isWaiting();}); 
    }



}
