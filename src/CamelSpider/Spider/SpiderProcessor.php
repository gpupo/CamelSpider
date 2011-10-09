<?php

namespace CamelSpider\Spider;
use CamelSpider\Entity\Link,
Zend\Uri\Uri;
class SpiderProcessor
{
    protected $config;

	protected $goutte; 
    
	protected $logger;
	
    protected $elements;

    protected $cache;

    private $requests = 0;

    protected $subscription;

    private $timeStart;
	/**
	* Recebe instância de https://github.com/fabpot/Goutte
	* e do Monolog
	**/
    public function __construct($goutte, $cache,  $logger, $config = NULL)
    {
        $this->timeStart = microtime(true);
        $this->goutte = $goutte;
        $this->logger = $logger;
        $this->cache = $cache;
        $this->elements  = new SpiderElements;

        if($config){
            $this->config = $config;
        }else{
            $this->config = array(
                'requests_limit'        =>      300,
                'memory_limit'          =>      100,
            );
        }
        return $this;
    }	

	protected function logger($string, $type = 'info')
	{
		return $this->logger->$type('#CamelSpiderProcessor ' . $string);
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
        return microtime(true) - $this->timeStart;
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
        var_dump($this->elements);
    }

    public function getResume(){

        $template = <<<EOF


======================RESUME===========================

    - Memory usage:         %s Mb
    - Number of Requests:   %s
    - Time:                 %s


EOF;

        return sprintf(
            $template,
            $this->getMemoryUsage(),
            $this->requests,
            $this->getTimeUsage()
        );


	}

	public function getPool()
	{
        $pool =  $this->elements->getPool();
        if($pool->count() < 1)
        {
            $this->logger('Pool empty');
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

            return $client;
		}
		catch(\Zend\Http\Client\Adapter\Exception\TimeoutException $e)
		{
			$this->logger( 'faillure on create a crawler [' . $URI . ']', 'err');	
		}
		
	}

    protected function getDocument($raw)
    {
    }
    
    
    
    /**
    * Verificar data container se link já foi catalogado.
    * Se sim, fazer idiff e rejeitar se a diferença for inferior a x%
    * Aplicar filtros contidos em $this->subscription
    **/
    protected function getRelevancy($raw)
    {
        return 0;
    }    


    /**
    * Processa o conteúdo do documento.
    * Neste momento são aplicados os filtros de conteúdo
    * e retornando flag se o conteúdo é relevante
    **/
	protected function getResponse()
	{
	    $response = array();	
        $response['raw'] = $this->goutte->getResponse();
        $response['relevancy'] = $this->getRelevancy($response['raw']);
        
        if($response['relevancy'] > 0){
            $response['document'] = $this->getDocument($response['raw']);
        } else {
            unset($response['raw']); //free memory
        }           
        return $response;
        

	}
	protected function getDomain()
	{
		return $this->subscription->get('domain');
	}
	
	protected function saveLink(Link $link)
    {
        if($link->isDone()){
            $this->cache->save($link->getId(), $link);
        }
        
        $this->elements->set($link->getId(), $link->getMinimal());

    }
    protected function errLink($link, $cause = 'undefined')
    {
        $link->set('status', 3);
        $this->elements->set($link->getId(), $link);
        $this->logger($link->get('href')  . ' marked with error. Cause [' . $cause . ']');
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
		if(substr($link->get('href'), 0, 4) == 'http' && 
			stripos($link->get('href'), $this->getDomain()) === false
		){
			$this->logger('outside the scope of ['.$this->getDomain().']:[' . $link->get('href') . ']');
		    return false;
    }
    return true;

  }	
	protected function processAddLink($link)
    {

        //Evita duplicidade
		if($this->cache->isObject($link->getId())){
		    $this->logger('cached:[' . $link->get('href') . ']');
		    return false;
		}
		
        if(!$this->insideScope($link)){
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
            if(!$crawler = $this->getCrawler($URI)){
                $this->logger('Crawler broken', 'err');
                $this->errLink($target, 'impossible crawler');
                return false;
            }    
		    
            $this->logger('processing document');    
			$target->set('response', $this->getResponse());
		    $target->set('status', 1); //done!
            if($withLinks){
                $this->logger('go to the scan of links!');
                $target->set('linksCount', $this->collectLinks($crawler));
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
    
        $this->logger( 'Number of links founded:' . $aCollection->count());
        
        foreach($aCollection as $node)
        {
            
            $link = new Link($node);
            $this->processAddLink($link);
            
        }
        return  $aCollection->count();	
	}

    protected function poolCollect($withLinks = false)
    {
        if(!$pool = $this->getPool()){
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

            echo $this->debug();
        }
    }    
	public function checkUpdates($subscription, $recursive = 0)
	{

		$this->subscription = $subscription;
		$this->collect($this->subscription, true);
		
        //coletando links e conteúdo
        $i = 0;
        while($i < $this->subscription->get('recursive')){
            $this->poolCollect(true);
        }          

        //agora somente o conteúdo
		$this->poolCollect();	

		echo $this->debug();
	
	}
	
}
