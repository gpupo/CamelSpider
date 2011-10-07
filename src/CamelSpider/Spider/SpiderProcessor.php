<?php

namespace CamelSpider\Spider;

class SpiderProcessor
{
	protected $goutte; 
    
	protected $logger;
	/**
	* Recebe instância de https://github.com/fabpot/Goutte
	* e do Monolog
	**/
    public function __construct($goutte, $logger)
    {
        $this->goutte = $goutte;
	    $this->logger = $logger;
		return $this;
    }

	protected function logger($string, $type = 'info')
	{
		return $this->logger->$type('#CamelSpiderProcessor ' . $string);
	}

	public function getCrawler($URI, $mode = 'GET')
	{
		
		// reiniciar instancia
		//$this->goutte->insulate();
		
	   	return  $this->goutte->request($mode, $URI);
		
	}

	protected function getLinks($crawler)
	{
		$aCollection = $crawler->filter('a');
		
		$this->logger( 'links:' . $aCollection->count());
		
		$aCollection->reduce(function ($node, $i)
		{
            $href = $node->getAttribute('href');
			if (empty($href) || 
			in_array($href,array('#', ""))) {
        		return false;
        	}
	    });
		
		$links = $aCollection->each(function ($node, $i){
		    return $node->getAttribute('href');
			});
		
		return $links;
		
	}
    protected $recursive = 2;
	public function checkUpdates($URI, $recursive = 0)
	{

		$staticRecursive = $recursive + 1;
		$crawler = $this->getCrawler($URI);
		$data = '';

        //Instrospecção
        if($staticRecursive <= $this->recursive){

		    foreach($this->getLinks($crawler) as $link)
		    {
				echo $link . "\n";
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
		
		return $data;
	
	}
	
}
