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
    CamelSpider\Entity\InterfaceSubscription,
    CamelSpider\Spider\SpiderAsserts as a,
    Zend\Uri\Uri;

/**
 * Process every subscription
 *
 * @package     CamelSpider
 * @subpackage  Spider
 * @author      Gilmar Pupo <g@g1mr.com>
 *
*/
class SpiderProcessor extends AbstractSpider
{

    protected $name = 'Processor';

    /**
    * @param \Goutte\Client Goutte $goutte Crawler Goutte
    * @param InterfaceCache $cache A class facade for Zend Cache
    * @param Monolog $logger Object to write logs (in realtime with low memory usage!)
    * @param array $config Overload of default configurations in the constructor
    **/
    public function __construct(\Goutte\Client $goutte, InterfaceCache $cache, $logger = NULL, array $config = NULL)
    {
        $this->timeStart = $this->timeParcial = microtime(true);
        $this->goutte = $goutte;
        $this->logger = $logger;
        $this->cache = $cache;
        $this->elements  = new SpiderElements;
        parent::__construct(array(), $config);
        return $this;
    }

    public function debug()
    {
        echo $this->getResume();
    }

    public function getResume()
    {

        $template = <<<EOF
 ====================RESUME=========================
    %s
    - Memory usage...........................%s Mb
    - Number of new requests.................%s 
    - Time...................................%s Seg
    - Objects in cache.......................%s
    - Errors.................................%s

EOF;

        return "\n\n"
            . sprintf(
                $template,
                $this->subscription,
                $this->getMemoryUsage(),
                $this->requests,
                $this->getTimeUsage(),
                $this->cached,
                $this->errors
            );


    }

    public function getPool($mode)
    {
        $pool =  $this->elements->getPool();
        if($pool->count() < 1)
        {
            $this->logger('Pool empty on the ' . $mode);
            return false;
        }
        $this->logger('Pool count:' . $pool->count());
        return $pool;
    }

    /**
     * @TODO: passar saveLink para Elements
     */
    protected function saveLink(InterfaceLink $link)
    {
        if($link->isDone()){
            $this->cache->save($link->getId(), $link, $this->getLinkTags());
        }

        $this->elements->set($link->getId(), $link->getMinimal());

    }

    protected function errLink($link, $cause = 'undefined')
    {
        $link->set('status', 3);
        $this->elements->set($link->getId(), $link);
        $this->logger($link->get('href')  . ' marked with error. Cause [' . $cause . ']');
        $this->errors++;
    }

	protected function isValidLink($href)
	{
		if(
			
			stripos($href, 'mail') == true ||
			
		 	empty($href) ||
		 
			substr($href, 0,10) == 'javascript' ||
		 
			substr($href, 0, 1) == '#'
		)
		{
			$this->logger('HREF descarted:[' . $href. ']', 'info');
			return false;
		}
		
		$zendUri = new Uri($href);
		if($zendUri->isValid()){
			return true;
		}
		$this->logger('HREF malformed:[' . $href. ']', 'info');
		return false;
    }

    protected function processAddLink($link)
    {

        if(!$this->subscription->insideScope($link)){
            
            $this->logger(
                'outside the scope of ['
                . $this->subscription->getDomainString()
                . ']:[' 
                . $link->get('href') 
                . ']'
            );

            return false;
        }

        //Evita duplicidade
        if($this->requests > 0 && $this->cache->isObject($link->getId())){
            $this->logger('cached:[' . $link->get('href') . ']');
            $this->cached++;
		    return false;
		}
		
    //Evita links inválidos
		if(!$this->isValidLink($link->get('href')))					
		{
		    return false;
		}
		
		return $this->saveLink($link);
	}
	
    protected function collect($target, $withLinks = false)
    {
        $URI = $target->get('href');
		try{
			if(!$this->isValidLink($URI)){
			    $this->logger('URI wrong:[' . $URI . ']', 'err');
                $this->errLink($target, 'invalid URL');
			    return false;
            }

            try{
                $crawler = $this->getCrawler($URI);
            }
            catch(\Exception $e){
                $this->logger($e->getMessage(), 'err');
                if($this->requests === 0){
                    $this->errors++;
                    throw new \Exception ('Error in the first request:' . $e->getMessage());
                } 
            }

            if(!$crawler){
                $this->logger('Crawler broken', 'err');
                $this->errLink($target, 'impossible crawler');
                return false;
            }    


            if($target instanceof Link){
                //Verifica se a diff do documento coletado com o documento
                //existente em DB é maior que X %
                if(DocumentManager::isFresh($this->getBody(), $target, $this->getSubscription())){
                    $target->setDocument(clone $crawler, $this->getSubscription(), $this->transferDependency());
                    $this->logger('document IS fresh');
                }
                else{
                    $this->logger('document isnt fresh');
                }
            }
		    $target->set('status', 1); //done!
            if($withLinks){
                $this->logger('go to the scan more links!');
                try{
                    $target->set('linksCount', $this->collectLinks($crawler));
                }
                catch(\Exception $e)
                {

                    $this->logger($e->getMessage(), 'err');
                    $this->debug();
                    die($e->getMessage() . "!\n");
                }

            }
            $this->logger('saving object on cache');
            $this->saveLink($target);
            return true;
        }
		catch(\Zend\Http\Exception\InvalidArgumentException $e)
		{
            $this->logger( 'Invalid argument on [' . $URI . ']', 'err');
            $this->errLink($target, 'invalid argument on HTTP request');
            throw new \Exception ('Invalid argument');
		}
        catch(\Zend\Http\Client\Adapter\Exception\RuntimeException $e)   
        {
			$this->logger( 'Http Client Runtime error on  [' . $URI . ']', 'err');
            $this->errLink($target, 'Runtime error on Http Client Adaper');
            return false;
        }

    }

	protected function collectLinks($crawler)
	{
        $aCollection = $crawler->filter('a');
        $this->logger(
            'Number of links founded in request #' 
            . $this->requests . ':' . $aCollection->count()
        );

        foreach($aCollection as $node)
        {
            $link = new Link($node);
            $this->processAddLink($link);
        }

        if($aCollection->count() < 1 && $this->requests === 0){
            throw new \Exception('Error on collect links in the index');
        }
	}

    protected function poolCollect($withLinks = false)
    {
        if(!$pool = $this->getPool('test')){
            return false;
        }

        foreach($pool as $link){
            
            
            if(!$this->checkLimit()){
                $this->errLink($link, 'Limit reached');
                break;
                //return false;
            }
            $this->logger( '====== Request number #' . $this->requests . '======');
            try{
                $this->collect($link, $withLinks);
            }
            catch(\Exception $e){
                $this->logger('Pool cant collect:' . $e->getMessage(), 'err');
            }

		    $this->logger($this->getResume());
        }
    }

    protected function restart()
    {
        $this->goutte->restart();
        $this->requests = $this->errors = 0;
        $this->elements = new SpiderElements;
    }

    protected function performLogin()
    {
        /**
         * @todo Verifica se a assinatura precisa login
         * Usa o Goutte para logar
         * Verifica o tipo de login requerido
         */
    }
    public function checkUpdate(InterfaceSubscription $subscription)
	{

        $this->restart();
        $this->subscription = $subscription;
        $this->performLogin();
		$this->collect($this->subscription, true);
		
        //coletando links e conteúdo
        $i = 0;
        while(
            $i < $this->subscription->getMaxDepth()
            && $this->getPool('looping')
        ){
            $this->poolCollect(true);
        }

        //agora somente o conteúdo se ainda existir algo na fila
        if($this->getPool('conclusion')){
            $this->poolCollect();	
        }

        /**
         * print resume on CLI 
         **/
        echo $this->getResume();
        return $this->elements;
	}
}
