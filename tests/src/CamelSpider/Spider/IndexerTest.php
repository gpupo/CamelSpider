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

    public function testCookiesHell()
    {
        $client = new Client();
        $url = 'http://www.agricultura.gov.br/comunicacao/noticias/';
        $crawler = $client->request('GET', $url);
        $request  = $client->getRequest();
        $response = $client->getResponse();
        $crawler  = $client->getCrawler();
        
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );

    }

    /**
     * @dataProvider providerNavegation()
     */
    public function testNavegation($host, $paths)
    {
        $client = new Client();
        //Test with absolute path
        foreach ($paths as $path) {
            $uri = 'http://' . $host . $path;
            $crawler = $client->request('GET', $uri);
            $this->assertEquals(200, $client->getResponse()->getStatus());
            $this->assertEquals($uri, $client->getRequest()->getUri());
        }
        //Test with relative path and get absolute URI
        foreach ($paths as $path) {
            $uri = 'http://' . $host . $path;
            $crawler = $client->request('GET', $path);
            $this->assertEquals(200, $client->getResponse()->getStatus());
            $this->assertEquals($uri, $client->getRequest()->getUri());
        }
    }

    /**
     * @dataProvider providerNavegation()
     */
    public function testWrongNavegation($host, $paths)
    {
        $client = new Client();
        //Test with absolute path
        $uri = 'http://' . $host . '/some' . rand();
        $crawler = $client->request('GET', $uri);
        $this->assertEquals(404, $client->getResponse()->getStatus());
    }

    public function providerNavegation()
    {
        $a = array();

        $a[] = array(
            'host'  =>  'diversao.terra.com.br',
            'paths' =>  array('/', '/tv/')
        );

        $a[] = array(
            'host'  =>  'www.mozilla.org',
            'paths' =>  array('/en-US/firefox/new/', '/en-US/firefox/features/', '/en-US/mobile/faq/')
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
