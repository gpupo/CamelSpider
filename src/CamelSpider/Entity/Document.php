<?php

namespace CamelSpider\Entity;

use Doctrine\Common\Collections\ArrayCollection,
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


class Document extends ArrayCollection
{

    private $crawler;

    private $response;

    private $subscription;

    private $logger;

    private $asserts;

    private $bigger = NULL;

    /**
     * Recebe a response HTTP e também dados da assinatura,
     * para alimentar os filtros que definem a relevânca do
     * conteúdo
     **/
    public function __construct(Crawler $crawler, InterfaceSubscription $subscription, $dependency = NULL)
    {
        $this->crawler = $crawler;
        $this->subscription = $subscription;

        if($dependency){
            foreach(array('logger', 'cache', 'config') as $k){
                if(isset($dependency[$k])){
                    $this->$k = $dependency[$k];
                }
            }
        }
        $this->set('relevancy',  0);
        $this->processResponse();
    }

	protected function logger($string, $type = 'info')
	{
        if($this->logger){
            return $this->logger->$type('#CamelSpiderEntityDocument ' . $string);
        }
    }
    protected function setTitle()
    {
        $title = $this->crawler->filter('title')->text();
        $this->set('title', $title);
        $this->logger('setting Title as [' . $title . ']');
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

        if(!$this->bigger || strlen($this->bigger->nodeValue) < 200)
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

    /**
     * Compara documento atual com possível documento em DB
     * Se encontra, compara diferenças.
     * Se diferenças menores que 40%,
     * invalida.
     *
     * @todo metodo para consulta a DB ?
     **/
    protected function checkDiff()
    {
        $this->logger('validating diff');
        //$this->subscription->getLink($this->getId())
        return true;
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
        $this->cache->saveDomToHtmlFile($this->bigger, $this->get('slug'));
        $this->cache->saveDomToTxtFile($this->bigger, $this->get('slug'));
    }
    /**
     * Converte o elemento com maior probabilidade de
     * ser o container do conteúdo em plain text
     */
    protected function setText()
    {
        $this->saveBiggerToFile();
        $this->set('text', SpiderDom::toText($this->bigger));

    }
    protected function setSlug()
    {
        $this->set('slug', substr(Urlizer::urlize($this->get('title')), 0, 30));
    }
    protected function processResponse()
    {
        $this->logger('processing');

        if(!$this->checkDiff()){
            return false;
        }

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
