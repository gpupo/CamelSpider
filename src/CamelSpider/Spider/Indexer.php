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

use CamelSpider\Entity\Link,
    CamelSpider\Entity\InterfaceLink,
    CamelSpider\Entity\Document,
    CamelSpider\Entity\Pool,
    CamelSpider\Entity\InterfaceSubscription,
    CamelSpider\Spider\SpiderAsserts as a;

/**
 * Process every subscription
 *
 * @author Gilmar Pupo <g@g1mr.com>
 *
*/
class Indexer extends AbstractSpider
{
    protected $name = 'Indexer';

    /**
    * @param \Goutte\Client Goutte $goutte Crawler Goutte
    * @param InterfaceCache $cache A class facade for Zend Cache
    * @param InterfaceFeedReader $feedReader A Zend Feed Reader Object
    * @param Monolog $logger Object to write logs (in realtime with low memory usage!)
    * @param array $config Overload of default configurations in the constructor
    **/
    public function __construct(\Goutte\Client $goutte = null, InterfaceCache $cache = null
        , InterfaceFeedReader $feedReader = null,  $logger = null, array $config = null)
    {
        $this->setTime('total');
        $this->goutte = $goutte;
        $this->logger = $logger;
        $this->feedReader = $feedReader;
        $this->cache = $cache;
        parent::__construct(array(), $config);

        return $this;
    }

    /**
     * Collect links in rss and atom feed
     *
     * @return int
     */
    public function collectLinksWithZendFeedReader(InterfaceFeedReader $reader)
    {
        foreach($reader->getLinks()->toArray() as $link) {
            if($this->checkLimit()){
                $this->hyperlinks +=  $this->addLink($link);
            }
        }

        return $reader->getLinks()->count();
    }

    /**
     * Método principal que faz indexação
     * Main method for indexing
     *
     * @param CamelSpider\Entity\InterfaceSubscription $subscription
     */
    public function run(InterfaceSubscription $subscription)
    {
        $this->restart();
        $this->subscription = $subscription;

        if ($this->performLogin() === false) {
            $this->addBackendLogger('Login Failed');

            return $this->getCapture();
        } else {
            $this->collect($this->subscription, true);

            $i = 0;
            while ($i < $this->subscription->getMaxDepth()) {
                $this->poolCollect(true);
                $i++;
            }

            $this->poolCollect(); //conclusion

            return $this->getCapture();
        }
    }

    protected function addLink(Link $link)
    {
        if (!$this->subscription->insideScope($link)) {

            $this->logger(
                'outside the scope'
                    . "\n"
                    . '['
                    . $this->subscription->getDomainString()
                    . "]\n["
                    . $link->get('href')
                    . ']'
                ,'info', 5);

            return 0;
        }

        //Evita links inválidos
        //Prevents invalid links
        if (!SpiderAsserts::isDocumentLink($link)) {
            $this->logger('Href refused', 'info', 5);

            return 0;
        }

        $this->logger('Check Cache for id:' . $link->getId('string'), 'info', 5);
        //Evita duplicidade
        //Prevents duplicates
        if ($this->requests > 0 && $this->cache->isObject($link->getId('string'))) {
            $this->logger('cached', 'info', 5);
            $this->cached++;

            return 0;
        }
        $this->pool->save($link);

        return 1;
    }

    protected function collect(InterfaceLink $target, $withLinks = false)
    {
        $URI = $target->getHref();
        $type = 'html';
        if($target instanceof InterfaceSubscription) {
            $this->logger("Subscription Type: " . $target->getSourceType());
            $type = $target->getSourceType();
        }

        try {
            if(!SpiderAsserts::isDocumentHref($URI)){
                $this->logger('URI wrong:[' . $URI . ']', 'err', 3);
                $this->pool->errLink($target, 'invalid URL');

                return false;
            }
            //verifica se já foi processado
            // verify that this has been processed
            if (!$target instanceof InterfaceSubscription && $this->isDone($URI)) {
                $this->logger('URI is Done:[' . $URI . ']', 'info', 1);

                return false;
            }

            try {
                $crawler = $this->getCrawler($URI, 'GET', $type);
            }
            catch(\Exception $e){
                $this->logger('Collect Exception', 'err', 3);
                $this->logger($e->getMessage(), 'err', 3);
                if($this->requests === 0){
                    $this->errors++;
                    throw new \Exception ('Error in the first request:' . $e->getMessage());
                }
            }

            if (!isset($crawler)) {
                $this->logger('Crawler broken', 'err');
                $this->pool->errLink($target, 'impossible crawler');

                return false;
            }

            if (!$target instanceof InterfaceSubscription) {
                if(
                    DocumentManager::isFresh(
                        $this->getBody(),
                        $target, $this->getSubscription()
                    )
                ){
                    $target->setDocument(
                        $this->getCurrentUri(),
                        clone $crawler,
                        $this->getSubscription(),
                        $this->transferDependency()
                    );
                    $this->logger('document IS fresh', 'info', 5);
                } else {
                    $this->logger('document isnt fresh');
                }
            }

            $target->setStatus(1);//done!

            if ($withLinks) {
                $this->logger('go to the scan more links!', 'info', 5);
                try {
                    $target->set('hyperlinks', $this->collectLinks($crawler, $type));
                } catch(\Exception $e) {
                    $this->logger($e->getMessage(), 'err');
                    $this->errors++;
                }
            }

            $this->logger(
                'saving object on cache, with id:'
                    . $target->getId('string'), 'info', 5
            );
            $this->pool->save($target);
            $this->success++;

            return true;
        }
        catch (\Zend\Http\Exception\InvalidArgumentException $e) {
            $this->logger( 'Invalid argument on [' . $URI . ']', 'err');
            $this->pool->errLink($target, 'invalid argument on HTTP request');
            $this->errors++;
            throw new \Exception ('Invalid argument');
        }
        catch (\Zend\Http\Client\Adapter\Exception\RuntimeException $e) {
            $this->logger( 'Http Client Runtime error on  [' . $URI . ']', 'err');
            $this->pool->errLink($target, 'Runtime error on Http Client Adaper');
            $this->errors++;

            return false;
        }
    }

    /**
     * Factory
     *
     * @return int
     */
    protected function collectLinks($obj, $mode = 'crawler')
    {
        $this->logger(
            'Coletando links em modo [' . $mode . ']', 'info', 4
        );
        switch ($mode) {
            case 'crawler':
            case 'html':
                return $this->collectLinksWithCrawler($obj);
                break;
            case 'zend_feed_reader':
            case 'rss':
            case 'atom':
                return $this->collectLinksWithZendFeedReader($obj);
                break;
        }
    }

    /**
     * Collect links in simple HTML
     *
     * @return int count of links
     */
    protected function collectLinksWithCrawler($crawler)
    {
        $aCollection = $crawler->filter('a');

        if ($aCollection->count() < 1 && $this->requests === 0) {
            throw new \Exception('Error on collect links in the index');
        }

        $this->logger(
            'Number of links founded in request #'
                . $this->requests
                . ':'
                . $aCollection->count()
                . ' with Goutte Crawler'
            , 'info'
            , 5
        );

        foreach($aCollection as $node) {
            if($this->checkLimit()) {
                $link = new Link($node);
                $this->hyperlinks +=  $this->addLink($link);
            }
        }

        return $aCollection->count();
    }

    protected function getCapture()
    {
        echo $this->getResume();

        return array(
            'log'   =>  $this->getBackendLogger(),
            'pool'  =>  $this->pool->getPackage()
        );
    }

    protected function isDone($URI)
    {
        $link = new Link($URI);

        return $this->pool->isDone($link);
    }

    protected function poolCollect($withLinks = false)
    {
        if (!$pool = $this->pool->getPool('collect')) {
            return false;
        }

        foreach ($pool as $link) {
            if (!$link instanceof InterfaceLink) {
                break;
            }

            if (!$this->checkLimit()) {
                $this->pool->errLink($link, 'Limit reached');
                break;
            }
            echo '. ';
            $this->logger(
                "\n"
                    . '====== Request number #'
                    . $this->requests
                    . '======',
                'info', 5
            );

            try{
                $this->collect($link, $withLinks);
            }
            catch(\Exception $e){
                $this->errors++;
                $this->logger('Can\'t collect:' . $e->getMessage(), 'err');
            }

            $this->logger($this->getResume(), 'info', 5);
        }
    }
}
