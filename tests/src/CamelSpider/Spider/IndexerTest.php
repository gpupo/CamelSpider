<?php

namespace CamelSpider\Spider;

use Goutte\Client;

class IndexerTest extends \PHPUnit_Framework_TestCase {

    /**
     * @dataProvider providerAuth()
     */
    public function testAuthToArray($auth, $len)
    {
        $spider = new Indexer;
        $auto = 3;
        $this->AssertEquals($len + $auto, count($spider->getAuthCredentials($auth)));
    }


    /**
     * @dataProvider providerAuth()
     */
    public function testLoginForm($auth)
    {
        $spider = new Indexer;
        $credentials = $spider->getAuthCredentials($auth);

        $this->assertArrayHasKey('type', $credentials);

        foreach ($spider->loginFormRequirements() as $r) {
            if (!array_key_exists($r, $credentials)) {
                $this->setExpectedException('Exception');
                $spider->loginForm($credentials);
            }
        }

    }


    /**
     * @dataProvider providerNavegation()
     */
    public function testParameters($host, $paths)
    {

        $client = new Client();

        //Test with absolute path
        foreach ($paths as $path) {
            $crawler = $client->request('GET', 'http://' . $host . $path);
            $server = $client;

            $this->assertEquals(200, $client->getResponse()->getStatus());
            var_dump($client->getRequest()->uri);
  // $this->assertTrue($crawler->statusCode); 
        }


       // $crawler = $client->request('GET', '/tv/noticias/');
       //$headers = $client->getHeaders();
        //var_dump($crawler);
        //
                //var_dump($server['HTTP_REFERER']);
        //var_dump($client->getHistory());

    }

    public function providerNavegation()
    {
        $a = array();

        $a[] = array(
            'host'  =>  'diversao.terra.com.br',
            'paths' =>  array('/', '/tv/')
        );

        return $a;
    }

    public function providerAuth()
    {
        $s = '';
        $i = 0;
        $a = array();
        foreach (array('something', 'button', 'username', 'password', 'expected') as $n) {
            $s .= '"'. $n . '":"' . $n . '"' . "\n";
            $i++;
            $a[] = array($s, $i);
        }
        return $a;
    }


}
