<?php

namespace CamelSpider\Entity;

use CamelSpider\Entity\AbstractSpiderEgg,
    Symfony\Component\DomCrawler\Crawler,
    Symfony\Component\BrowserKit\Response,
    CamelSpider\Spider\SpiderAsserts,
    CamelSpider\Spider\SpiderDom,
    CamelSpider\Entity\InterfaceSubscription,
    CamelSpider\Tools\Urlizer;

/**
 * Contain formated response
 *
 * @package     CamelSpider
 * @subpackage  Entity
 * @author      Gilmar Pupo <g@g1mr.com>
 *
 */


class Document extends AbstractSpiderEgg
{
    protected $name = 'Document';

    private $crawler;

    private $response;

    private $subscription;

    private $asserts;

    private $bigger = NULL;

    /**
     * Recebe a response HTTP e também dados da assinatura,
     * para alimentar os filtros que definem a relevânca do
     * conteúdo
     *
     * Config:
     *
     *
     * @param array $dependency Logger, Cache, array Config
     *
     **/
    public function __construct(Crawler $crawler, InterfaceSubscription $subscription, $dependency = NULL)
    {
        $this->crawler = $crawler;
        $this->subscription = $subscription;

        if($dependency){
            foreach(array('logger', 'cache') as $k){
                if(isset($dependency[$k])){
                    $this->$k = $dependency[$k];
                }
            }
        }
        $config = isset($dependency['config']) ? $dependency['config'] : NULL;
        parent::__construct(array('relevancy'=>0), $config);
        $this->processResponse();
    }

	    protected function setTitle()
    {
        $title = $this->crawler->filter('title')->text();
        $this->set('title', trim($title));
        $this->logger('setting Title as [' . $this->getTitle() . ']');
    }

    public function getTitle()
    {
        return $this->get('title');
    }

    protected function getBody()
    {
        return $this->crawler->filter('body');
    }
    /**
     * Faz uma query no documento,
     * de acordo com os parâmetros definidos 
     * na assinatura
     * @todo implementar!
     **/
    protected function setRelevancy()
    {
        if(!$this->bigger)
        {
            $this->logger('Content too short');
        }
        else
        {
            $this->addRelevancy();
        }
        $this->addRelevancy(); //esperando implementação!!
    }
    protected function addRelevancy()
    {
        $this->set('relevancy', $this->get('relevancy') + 1);
    }

    protected function diffValue($a, $b)
    {

    }

    /**
     * localiza a tag filha de body que possui maior
     * quantidade de texto
     */
    protected function searchBiggerInTags($tag)
    {
        $data = $this->crawler->filter($tag);

        foreach(clone $data as $node)
        {
            if(SpiderDom::containerCandidate($node)){
                $this->bigger = SpiderDom::getGreater($node, $this->bigger);
                $this->saveBiggerToFile();
            }
        }
    }


    protected function getBiggerTag()
    {
        foreach(array('div', 'td', 'span') as $tag){
            $this->searchBiggerInTags($tag);
        }
        if(! $this->bigger instanceof \DOMElement ) {
            $this->logger('Cannot find bigger', 'err');
            return false;
        }
    }

    protected function saveBiggerToFile()
    {
        $title = '# '. $this->getTitle() . "\n\n";
        $this->cache->saveDomToHtmlFile($this->bigger, $this->get('slug'));
        $this->cache->saveDomToTxtFile($this->bigger, $this->get('slug'), $title);
    }
    /**
     * Converte o elemento com maior probabilidade de
     * ser o container do conteúdo em plain text
     */
    protected function setText()
    {
        if($this->bigger){
            $this->set('text', SpiderDom::toText($this->bigger));
        }
        else
        {
            $this->set('text', NULL);
        }
    }

    protected function setSlug()
    {
        $this->set('slug', substr(Urlizer::urlize($this->get('title')), 0, 30));
    }
    protected function processResponse()
    {
        $this->logger('processing');
        $this->setTitle();
        $this->setSlug();
        $this->getBiggerTag();
        $this->setRelevancy();

        if($this->getRelevancy() > 0)
        {
            $this->setText();   
        }
    }

    /**
    * Verificar data container se link já foi catalogado.
    * Se sim, fazer idiff e rejeitar se a diferença for inferior a x%
    * Aplicar filtros contidos em $this->subscription
    **/
    protected function getRelevancy()
    {
        return $this->get('relevancy');
    }


}
