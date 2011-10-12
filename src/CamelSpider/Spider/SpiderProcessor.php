<?php

namespace CamelSpider\Spider;
use CamelSpider\Entity\Link,
    CamelSpider\Entity\InterfaceLink,
    CamelSpider\Entity\Document,
    CamelSpider\Entity\InterfaceSubscription,
    CamelSpider\Spider\SpiderAsserts as a,
    Zend\Uri\Uri;


class SpiderProcessor
{
    protected $config;

	protected $goutte;
    
	protected $logger;
	
    protected $elements;

    protected $cache;

    private $requests = 0;

    private $cached = 0;

    private $errors = 0;
    
    protected $subscription;

    private $timeStart;

    private $timeParcial;

	/**
	* Recebe instância de https://github.com/fabpot/Goutte
	* e do Monolog
	**/
    public function __construct($goutte, $cache,  $logger, $config = NULL)
    {
        $this->timeStart = $this->timeParcial = microtime(true);
        $this->goutte = $goutte;
        $this->logger = $logger;
        $this->cache = $cache;
        $this->elements  = new SpiderElements;

        if($config){
            $this->config = $config;
        }else{
            $this->config = array(
                'requests_limit'        =>      100,
                'memory_limit'          =>      60,
            );
        }
        return $this;
    }	

	protected function logger($string, $type = 'info')
	{
		return $this->logger->$type('#CamelSpiderProcessor ' . $string);
    }

    protected function getSubscription()
    {
        return $this->subscription;
    }

    /**
     * return memory in MB
     **/
    protected function getMemoryUsage()
    {
        return round((\memory_get_usage()/1024) / 1024);
    }
    protected function getTimeUsage()
    {
        return round(microtime(true) - $this->timeParcial);
    }
    protected function checkLimit()
    {
        $this->logger('Current memory usage:' . $this->getMemoryUsage() . 'Mb');

        if($this->getMemoryUsage() >= $this->config['memory_limit']){
           $this->logger('Limit of memory reached', 'err');
           return false;
        }
        if($this->requests >= $this->config['requests_limit']){
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

    public function getResume(){

        $template = <<<EOF
 ======================RESUME===========================
    * %s
    - Memory usage...........................%s Mb
    - Number of new requests.................%s 
    - Time...................................%s Seg
    - Objects in cache.......................%s
    - Errors.................................%s

EOF;

        return sprintf(
            $template,
            $this->subscription->getDomain(),
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
    * Processa o conteúdo do documento.
    * Neste momento são aplicados os filtros de conteúdo
    * e retornando flag se o conteúdo é relevante
    **/
	protected function getResponse()
	{
        return $this->goutte->getResponse();
	}
    
    protected function getRequest()
	{
        return $this->goutte->getRequest();
	}
    
    protected function getDomain()
	{
		return $this->subscription->get('domain');
	}
    
    protected function getLinkTags()
    {
        return array(
            'subscription_' . $this->subscription['id'],
            'crawler',
            'processor'
        );
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

    protected function insideScope($link)
    {
		//Evita sair do escopo
        if(
            substr($link->get('href'), 0, 4) == 'http' && 
			stripos($link->get('href'), $this->getDomain()) === false
		){
            $this->logger('outside the scope of ['
                .$this->getDomain()
                .']:[' 
                . $link->get('href') 
                . ']');
            
            return false;
        }
        
        return true;

    }

    protected function processAddLink($link)
    {

        if(!$this->insideScope($link)){
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
                    $this->debug();
                    
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
                $target->setDocument(clone $crawler, $this->getSubscription(), $this->logger);
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
    
        $this->logger( 'Number of links founded in request #' 
            . $this->requests . ':' . $aCollection->count());
        
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
                return false;
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

    public function checkUpdate(InterfaceSubscription $subscription)
	{

        $this->restart();
        $this->subscription = $subscription;
		$this->collect($this->subscription, true);
		
        //coletando links e conteúdo
        $i = 0;
        while($i < $this->subscription->get('recursive') && $this->getPool('looping')){
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
