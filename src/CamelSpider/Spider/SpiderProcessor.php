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

    protected $goutte;

    protected $elements;

    protected $cache;

    protected $requests = 0;

    protected $cached = 0;

    protected $errors = 0;

    protected $subscription;
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

    private function transferDependency()
    {
        return array(
            'logger' => $this->logger,
            'cache'  => $this->cache,
            'config' => $this->config
        );
    }
    protected function checkLimit()
    {
        $this->logger('Current memory usage:' . $this->getMemoryUsage() . 'Mb');

        if($this->getMemoryUsage() >= $this->getConfig('memory_limit', 80)){
           $this->logger('Limit of memory reached', 'err');
           return false;
        }
        if($this->requests >= $this->getConfig('requests_limit', 300)){
            //throw new \Exception ('Limit reached');
            $this->logger('Limit of requests reached', 'err');
            return false;
        }
        $this->requests++;
        return true;
    }

    public function debug()
    {
        //var_dump($this->elements);
        //var_dump($this->goutte);
        //var_dump($this->getRequest());
        //var_dump($this->getResponse());
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

        return sprintf(
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

    public function getCrawler($URI, $mode = 'GET')
    {

        $this->logger( 'created a Crawler for [' . $URI . ']');

        try {
            $client = $this->goutte->request($mode, $URI);
        }
        catch(\Zend\Http\Client\Adapter\Exception\TimeoutException $e)
        {
            $this->logger( 'faillure on create a crawler [' . $URI . ']', 'err');	
        }

        //Error in request
        $this->logger('Status Code: [' . $this->getResponse()->getStatus() . ']');
        if($this->getResponse()->getStatus() >= 400){
            throw new \Exception('Request with error: ' . $this->getResponse()->getStatus() 
                . " - " . $client->text()
            );
        }

        return $client;
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
                . $this->subscription->getDomain()
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
		$this->logger( 'trying to collect links in [' . $URI . ']');
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
                $this->logger('processing document');

                //Verifica se a diff do documento coletado com o documento
                //existente em DB é maior que X %

                $this->logger('validating if document is fresh');
                if(DocumentManager::isFresh($this->getBody(), $link, $this->getSubscription())){
                    $target->setDocument(clone $crawler, $this->getSubscription(), $this->transferDependency());
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
            $this->logger('pool start new collect'); 
            try{
                $this->collect($link, $withLinks);
            }
            catch(\Exception $e){
                $this->logger('Pool cant collect:' . $e->getMessage(), 'err');
            }

		    $this->logger($this->getResume());
            $this->logger('====== Request end ======');

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
