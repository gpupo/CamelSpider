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

use Zend\Feed\Reader\Reader,
    CamelSpider\Spider\InterfaceFeedReader,
    CamelSpider\Spider\InterfaceCache,
    CamelSpider\Spider\SpiderDom,
    CamelSpider\Entity\AbstractSpiderEgg,
    CamelSpider\Entity\Link,
    Doctrine\Common\Collections\ArrayCollection;

/**
 * Process rss and attom
 *
 * Using Zend Feed Reader
 *
 * @see http://framework.zend.com/manual/en/zend.feed.reader.html
 */
class FeedReader extends AbstractSpiderEgg implements InterfaceFeedReader
{
    protected $name = 'Feed Reader';
    protected $feed;
    private $uri;
    private $logger_level = 3;

    public function __construct(InterfaceCache $cache, $logger = NULL, array $config = NULL)
    {
        $this->cache  = $cache;
        $this->logger = $logger;
        //Reader::setCache($cache->getZendCache());
        //Reader::useHttpConditionalGet();
        parent::__construct(array(), $config);
    }

    public function request($uri)
    {
        $this->logger('Read Feed from ' . $uri, 'info' , $this->logger_level);
        $this->uri = $uri;
        $this->import();

        return $this;
    }

    public function import()
    {
        $this->logger('Import ' . $this->uri, 'info', $this->logger_level);
        try{
            $this->feed = Reader::import($this->uri);
            if (!isset($this->feed)) {
                throw new \Exception('Unreadble');
            }
        }
        catch (\Exception $e) {
            $this->logger('Feed empty ', 'err', $this->logger_level);
            return false;
        }
    }

    public function getLinks()
    {
        if (isset($this->links)) {
            return $this->links;
        }

        $this->links = new ArrayCollection;

        if (isset($this->feed)) {
            foreach ($this->feed as $item ) {
                $this->logger('Feed reader add link from rss:'. $item->getLink(), 'info', $this->logger_level);
                $this->links->add(new Link($item->getLink()));
            }
        }

        return $this->links;
    }

}
