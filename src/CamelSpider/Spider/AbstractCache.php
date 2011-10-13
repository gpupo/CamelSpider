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

    public function checkDir()
    {
        $this->mkdir($this->cache_dir);
        foreach(array('/html', '/txt') as $subdir){
            $this->mkdir($this->cache_dir . $subdir);
        }
    }

    protected function logger($string, $type = 'info')
    {
        if($this->logger){
            return $this->logger->$type('#CamelSpiderCache ' . $string);
        }
    }

    protected function mkdir($dir)
    {
        if (!is_dir($dir)) {
            $this->logger('Creating the directory [' . $dir . ']');
            if (false === @mkdir($dir, 0777, true)) {
                throw new \RuntimeException(sprintf('Unable to create the %s directory', $dir));
            }
        } elseif (!is_writable($dir)) {
            throw new \RuntimeException(sprintf('Unable to write in the %s directory', $dir));
        }
    }

    /**
     * Remove directory
     *
     * @todo Extender métodos de Zend Cache para remoção de arquivos
     */
    protected function rmdir($dir)
    {
        return true; //temporary
        if (is_dir($dir)) {
            $this->logger('Removing the directory [' . $dir . ']');
            if (false === @rmdir($dir)) {
                throw new \RuntimeException(sprintf('Unable to remove the %s directory', $dir));
            }
        }
    }

    public function clean($mode = Zend_Cache::CLEANING_MODE_ALL)
    {
        $this->rmdir($this->cache_dir . '/html');
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

    public function getFileRandomPath($slug, $format)
    {
        return $this->cache_dir
            . '/' 
            . $format 
            . '/' 
            . $slug 
            . '-' 
            . sha1(microtime(true)) 
            . '.'
            . $format;
    }

    public function saveDomToHtmlFile(\DOMElement $e, $slug)
    {
        $file = $this->getFileRandomPath($slug, 'html');
        $this->logger('saving DomElement as HTML file ' . $file);
        return SpiderDom::saveHtmlToFile($e, $file);
    }

    public function saveDomToTxtFile(\DOMElement $e, $slug)
    {
        $file = $this->getFileRandomPath($slug, 'txt');
        $this->logger('saving DomElement as TXT file ' . $file);
        return SpiderDom::saveTxtToFile($e, $file);
    }
}

