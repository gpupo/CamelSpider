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

    public function testParameters()
    {

        $client = new Client();
        $crawler = $client->request('GET', 'http://www.terra.com.br/portal/');
        $crawler = $client->request('GET', 'http://diversao.terra.com.br/tv/');
        $crawler = $client->request('GET', '/tv/noticias/');
       //$headers = $client->getHeaders();
        var_dump($client->getHistory());
        var_dump($crawler);

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
