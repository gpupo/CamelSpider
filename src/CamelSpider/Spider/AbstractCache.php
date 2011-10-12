<?php

/*
* This file is part of the CamelSpider package.
*
* (c) Gilmar Pupo <g@g1mr.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/


namespace CamelSpider\Spider;
use Zend\Cache\Cache as Zend_Cache,
    CamelSpider\Spider\InterfaceCache,
    CamelSpider\Spider\AbstractSpiderCache,
    CamelSpider\Spider\SpiderDom;

class AbstractCache implements InterfaceCache
{
    protected $cache_dir;
    protected $logger;
    protected $cache;

    protected function logger($string, $type = 'info')
    {
        if($this->logger){
            return $this->logger->$type('#CamelSpiderCache ' . $string);
        }
    }
    public function clean($mode = Zend_Cache::CLEANING_MODE_ALL)
    {
        return $this->cache->clean($mode);
    }

    public function save($id, $data, array $tags = array('undefined'))
    {
        if(empty($id)){
            $this->logger('Object id Empty!', 'err');
            return false;
        }
        $this->logger('Saving object ['. $id .']');
        return $this->cache->save($data, $id, $tags);
    }

    public function getObject($id)
    {
        $this->logger('Get object ['. $id .']');
        return $this->cache->load($id);
    }
    
    public function isObject($id)
    {
        $this->logger('Check object ['. $id .']');
        if($this->getObject($id) !== false){
            return true;
        }
    }
    
    public function saveDomToHtmlFile(\DOMElement $e, $slug)
    {
        $file = $this->cache_dir . '/' . $slug . '.html';
        $this->logger('saving DomElement as HTML in file ' . $file);
        SpiderDom::saveHtmlToFile($e, $file);
    }
}

