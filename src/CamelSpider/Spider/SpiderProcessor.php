<?php

namespace CamelSpider\Spider;
use CamelSpider\Entity\Link,
Zend\Uri\Uri;
class SpiderProcessor
{
	protected $goutte; 
    
	protected $logger;
	
	protected $cache;
	
	protected $subscription;
	/**
	* Recebe instância de https://github.com/fabpot/Goutte
	* e do Monolog
	**/
    public function __construct($goutte, $logger)
    {
        $this->goutte = $goutte;
	    $this->logger = $logger;
		$this->cache  = new SpiderCache;
		return $this;
    }

	protected function logger($string, $type = 'info')
	{
		return $this->logger->$type('#CamelSpiderProcessor ' . $string);
	}
	public function debug()
	{
		var_dump($this->cache);
	}
	public function getCrawler($URI, $mode = 'GET')
	{
		
		// reiniciar instancia ?
		//$this->goutte->insulate();
		$this->logger( 'created a Crawler for [' . $URI . ']');
	   	return  $this->goutte->request($mode, $URI);
		
	}

	protected function getResponse()
	{
		return $this->goutte->getResponse();
	}
	protected function getDomain()
	{
		return $this->subscription->get('domain');
	}
	
	protected function addLink(Link $link)
	{
		return $this->cache->set($link->get('href'), $link);
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
		//Evita duplicidade
		if($this->cache->containsKey($link->get('href'))){
			$this->logger('cached:[' . $link->get('href') . ']');
		    return false;
		}
		
		//Evita sair do escopo
		if(substr($link->get('href'), 0, 4) == 'http' && 
			stripos($link->get('href'), $this->getDomain()) === false
		){
			$this->logger('outside the scope of ['.$this->getDomain().']:[' . $link->get('href') . ']');
		    return false;
		}
		
		//Evita links inválidos
	  	if(!$this->isValidLink($link->get('href')))					
		{
			$this->logger('invalid link:[' . $link->get('href') . ']');
		    return false;
		}
		
		return $this->addLink($link);
	}
	
	
	protected function collectLinks(Link &$target)
	{
		$URI = $target->get('href');
		$this->logger( 'trying to collect links in [' . $URI . ']');
		try{
			if(!$this->isValidLink($URI)){
			    $this->logger('URI wrong:[' . $URI . ']', 'err');
			    return false;
			}	
			$crawler = $this->getCrawler($URI);
			
			$target->set('response', $this->getResponse());
					
						
			$aCollection = $crawler->filter('a');
		
			$this->logger( 'Number of links in [' . $URI . ']:' . $aCollection->count());
		
		
			foreach($aCollection as $node)
			{
				
				$link = new Link($node);
				$this->processAddLink($link);
				
			} 
		}
		catch(\Zend\Http\Exception\InvalidArgumentException $e)
		{
			$this->logger( 'faillure on [' . $URI . ']', 'err');
		}
		
	}
    protected $recursive = 2;
	public function checkUpdates($subscription, $recursive = 0)
	{

		$staticRecursive = $recursive + 1;
		$this->subscription = $subscription;
		$this->collectLinks($this->subscription) ;
		
		$data = '';

        //Instrospecção
        if($staticRecursive <= $this->recursive){

		    foreach($this->cache as &$link)
		    {
				if($link->indexOf('response')){
					$this->logger( 'already collected: [' . $link->get('href'). ']');	
					continue;
				}
				$this->collectLinks($link) ;
		    	 //$this->goutte->insulate();	
		    	 //$data .= $this->checkUpdates($link, $staticRecursive);
		    }
        }    

		$this->debug();
		return $data;
	
	}
	
}
