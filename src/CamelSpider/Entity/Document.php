<?php

namespace CamelSpider\Entity;

use Doctrine\Common\Collections\ArrayCollection,
    Symfony\Component\DomCrawler\Crawler,
    Symfony\Component\BrowserKit\Response,
    CamelSpider\Spider\SpiderAsserts,
    CamelSpider\Tools\Urlizer;


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

    public function __construct(Crawler $crawler, $subscription, $logger = NULL)
    {
        $this->crawler = $crawler;
        
        $this->logger = $logger;

        $this->subscription = $subscription;
        
        $this->asserts = new SpiderAsserts;

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
 * @TODO metodo para consulta a DB ?
 * */

    protected function checkDiff()
    {
        $this->logger('validating diff');
        return true;
    }
/**
 * localiza a tag filha de body que possui maior
 * quantidade de texto
 */

    protected function toInnerHtml(\DOMElement $node)
    {
        return $node->ownerDocument->saveXML($node);
    }

    protected function countInnerTags(\DOMElement $node, $tag)
    {
        $a = $node->getElementsByTagName($tag);
        return $a->length;
    }
    protected function searchBiggerInTags($tag)
    {

        $data = $this->crawler->filter($tag);

            
        foreach($data as $node)
        {
            $a = $node->getElementsByTagName('a');
            if(!$this->bigger ||
                (
                    strlen($node->nodeValue) > strlen($this->bigger->nodeValue) &&
                    $this->countInnerTags($node, 'a') < 15 && //limit number of links 
                    $this->countInnerTags($node, 'javascript') < 2
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
    
    protected function setText()
    {
        echo "\n" .'========================' . "\n";
        echo strip_tags($this->toInnerHtml($this->bigger), '<javascript><style>');
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

        $this->getBiggerTag();

        $this->setRelevancy();
        
        if($this->getRelevancy() > 0)
        {
            $this->setText();   
            $this->setTitle();
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
