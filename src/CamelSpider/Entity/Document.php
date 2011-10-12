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

        //$v = $this->asserts->assertRegExp('/Hello Fabien/', $this->goutte->getResponse()->getContent());
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
     * @TODO metodo para consulta a DB ?
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

        foreach($data as $node)
        {
            $a = $node->getElementsByTagName('a');
            if(!$this->bigger ||
                (
                    strlen($node->nodeValue) > strlen($this->bigger->nodeValue) &&
                    SpiderDom::countInnerTags($node, 'a') < 15 && //limit number of links 
                    SpiderDom::countInnerTags($node, 'javascript') < 2
                )
            ){
                $this->bigger = $node;
            }
        }
    }

    private $bigger = NULL;

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

    /**
     * Converte o elemento com maior probabilidade de
     * ser o container do conteúdo em plain text
     */
    protected function setText()
    {
        $this->cache->saveDomToHtmlFile($this->bigger, $this->get('slug'));
        $this->set('text', SpiderDom::toText($this->bigger));

    }
    protected function setSlug()
    {
        $this->set('slug', Urlizer::urlize($this->get('title')));
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

        //printf("body: %d\n", $this->crawler->filter('body')->text());
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
