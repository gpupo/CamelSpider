<?php

namespace CamelSpider\Spider;
use Zend\Uri\Uri;
class SpiderProcessor
{
	protected $goutte; 
    
	protected $logger;
	
	protected $cache;
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
		
		// reiniciar instancia
		//$this->goutte->insulate();
		
	   	return  $this->goutte->request($mode, $URI);
		
	}

	protected function getResponse()
	{
		return $this->goutte->getResponse();
	}
	protected function getDomain()
	{
		$server = $this->goutte->getRequest()->getServer();
		return $server['HTTP_HOST'];
	}
	
	protected function invalidLink($link)
	{
		return (
			( 
				substr($link, 0, 4) == 'http' && 
		 	  	stripos($link, $this->getDomain()) === false
		    ) ||
		
		 	empty($link) ||
		 
			substr($link, 0,10) == 'javascript' ||
		 
			substr($link, 0, 1) == '#'
		);
	}
	protected function validLink($URI)
	{
		$zendUri = new Uri($URI);
		if($zendUri->isValid()){
			return true;
		}
		$this->logger('URL malformed:[' . $URI . ']', 'err');
		return false;
	}
	protected function collectLinks($URI)
	{
		if(!$this->validLink($URI)){
			return false;
		}	
		$crawler = $this->getCrawler($URI);
		$aCollection = $crawler->filter('a');
		
		$this->logger( 'Number og links in [' . $URI . ']:' . $aCollection->count());
		
		
		$links = $aCollection->each(function ($node, $i){
		    return trim($node->getAttribute('href'));
			});
		
		foreach($links as $link)
		{
		  	if(!$this->invalidLink($link))					
			{
				$this->cache->append($link);
			}
		} 
		
		
	}
    protected $recursive = 2;
	public function checkUpdates($URI, $recursive = 0)
	{

		$staticRecursive = $recursive + 1;
		$this->collectLinks($URI) ;
		
		$data = '';

        //Instrospecção
        if($staticRecursive <= $this->recursive){

		    foreach($this->cache as $link)
		    {
				$this->collectLinks($link) ;
		    	 //$this->goutte->insulate();	
		    	 //$data .= $this->checkUpdates($link, $staticRecursive);
		    }
        }    
		//var_dump($data);
		//$this->goutte->getResponse()->getContent();
		#$response = $crawler->getResponse();
		//$client->getContainer();
	    #$content = $crawler->getContent();
		//var_dump($crawler->getResponse());
		//echo $this->getResponse();
		//var_dump($this->goutte->getRequest()->getServer());
		$this->debug();
		return $data;
	
	}
	
}
