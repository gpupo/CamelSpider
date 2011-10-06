<?php

namespace CamelSpider\Spider;

class SpiderProcessor
{
	protected $goutte; 
    
	protected $curl;
	/**
	* Recebe instância de https://github.com/fabpot/Goutte
	**/
    public function __construct(Goutte $goutte)
    {
        $this->gotte = $gotter;
		$this->curl = $this->gotter->getNamedClient('curl');
		return $this;
    }


	public function getCrawler($URI, $mode = 'GET')
	{
		return $this->curl->request($mode, $URI);
	}
	
	public function checkUpdates($subscription)
	{
		
		$crawler = $this->getCrawler('http://www.terra.com.br/portal/');
		$response = $crawler->getResponse();
	    $content = $crawler->getContent();
		
		
		var_dump($content);
	
	}
	
}
