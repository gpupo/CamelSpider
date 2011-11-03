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
        //echo "========\nDUMP:" . var_dump($this->crawler->filter('title'));
        //echo "\n";
        foreach($this->crawler->filter('title') as $node){
            echo '<title DOM:'. SpiderDom::toText($node) ."\n";
        }

        $title = $this->crawler->filter('title')->text();
        $this->set('title', trim($title));
        echo '<title getTitle:' .$this->getTitle() . "\n\n";
        $this->logger('setting Title as [' . $this->getTitle() . ']', 'info', 3);
    }

    public function getTitle()
    {
        return $this->get('title');
    }

    protected function getBody()
    {
        return $this->crawler->filter('body');
    }

    protected function getRaw()
    {
        if ($this->getBody() instanceof DOMElement) {
            return SpiderDom::toHtml($this->getBody());
        } else {
            return 'SpiderDom toHtml with problems!';
        }
    }

    /**
     * Faz query no documento, de acordo com os parâmetros definidos
     * na assinatura e define a relevância, sendo que esta relevância 
     * pode ser:
     *  1) Possivelmente contém conteúdo
     *  2) Contém conteúdo e contém uma ou mais palavras chave desejadas 
     *  pela assinatura ou não contém palavras indesejadas
     *  3) Contém conteúdo, contém palavras desejadas e não contém 
     *  palavras indesejadas
     **/
    protected function setRelevancy()
    {
        if(!$this->bigger)
        {
            $this->logger('Content too short', 'info', 3);
            return false;
        }
        $this->addRelevancy();//+1 cause text exist

        $txt = $this->getTitle() . "\n"  . $this->getText();

        $this->logger("Text to be verified:\n". $txt . "\n", 'info', 3);

        //diseribles keywords filter
        if (is_null($this->subscription->getFilter('contain'))) {
            $this->addRelevancy();
            $this->logger('ignore keywords filter', 'info' , 5);
        } else {
            //Contain?
            $this->logger('Check for keywords[' . implode($this->subscription->getFilter('contain')) . ']', 'info', 3);
            $containTest = SpiderAsserts::containKeywords($txt, (array) $this->subscription->getFilter('contain'), true);
            if($containTest) {
                $this->addRelevancy();
            } else {
                $this->logger('Document not contain keywords');
            }
        }

        //Bad words
        if (is_null($this->subscription->getFilter('notContain'))) {
            $this->addRelevancy();
            $this->logger('ignore Bad keywords filter', 'info' , 5);
        } else {
            //Not Contain?
            $this->logger('Check for BAD keywords[' . implode($this->subscription->getFilter('notContain')) . ']', 'info', 1);
            if(!SpiderAsserts::containKeywords($txt, $this->subscription->getFilter('notContain'), false)) {
                $this->addRelevancy();
            } else {
                $this->logger('Document contain BAD keywords');
            }
        }
    }

    protected function addRelevancy()
    {
        $this->set('relevancy', $this->get('relevancy') + 1);
        $this->logger('Current relevancy:'. $this->getRelevancy(), 'info', 3);
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
            }
        }
    }

    protected function getBiggerTag()
    {
        foreach(array('div', 'td', 'span') as $tag){
            $this->searchBiggerInTags($tag);
        }
        if(! $this->bigger instanceof \DOMElement ) {
            $this->logger('Cannot find bigger', 'info', 3);
            return false;
        }
    }

    protected function saveBiggerToFile()
    {
        $title = '# '. $this->getTitle() . "\n\n";
        $this->cache->saveToHtmlFile($this->getHtml(), $this->get('slug'));
        $this->cache->saveDomToTxtFile($this->bigger, $this->get('slug'), $title);
    }

    public function getHtml()
    {
        if ($this->bigger) {
            return SpiderDom::toHtml($this->bigger);
        }
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

    public function getText()
    {
        return $this->get('text');
    }

    protected function setSlug()
    {
        $this->set('slug', substr(Urlizer::urlize($this->get('title')), 0, 30));
    }

    public function getSlug(){
        return $this->get('slug');
    }

    protected function processResponse()
    {
        $this->logger('processing document' ,'info', 3);
        $this->getBiggerTag();

        if ($this->getConfig('save_document', false)) {
            $this->saveBiggerToFile();
        }

        $this->setText();
        $this->setRelevancy();
        $this->setTitle();
        $this->setSlug();
        $this->logger('Document processed:' . $this->getTitle() ,'echo', 3);
    }

    /**
    * Verificar data container se link já foi catalogado.
    * Se sim, fazer idiff e rejeitar se a diferença for inferior a x%
    * Aplicar filtros contidos em $this->subscription
    **/
    public function getRelevancy()
    {
        return $this->get('relevancy');
    }


    /**
     * reduce memory usage
     *
     * @return self minimal
     */
    public function toPackage()
    {
         $array = array(
            'relevancy' => $this->getRelevancy(),
            'title'     => $this->getTitle(),
            'slug'      => $this->getSlug(),
            'text'      => $this->getText(),
            'html'      => $this->getHtml(),
            'raw'       => $this->getRaw()
        );

        return $array;
    }

    /**
     * @return array $array
     */
    public function toArray()
    {
        $array = array(
            'relevancy' => $this->getRelevancy(),
            'title'     => $this->getTitle(),
        );

        return $array;
    }
}
