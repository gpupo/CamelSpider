<?php

namespace CamelSpider\Spider;

class SpiderAssertsTest extends \PHPUnit_Framework_TestCase {

    /**
     * @dataProvider providerDocumentHref
     */
    public function testValidDocumentHref($input) 
    {
        $this->assertTrue(SpiderAsserts::isDocumentHref($input));
    }

    /**
     * @dataProvider providerInvalidDocumentHref
     */
    public function testInvalidDocumentHref($input) 
    {
        $this->assertFalse(SpiderAsserts::isDocumentHref($input));
    }

    public function providerDocumentHref() 
    {
        return array(
            array('magica.html'),
            array('http://www.gpupo.com/about'),
            array('/var/dev/null.html')
        );
    }

    public function providerInvalidDocumentHref() 
    {
        return array(
            array('mailto:g@g1mr.com'),
            array('javascript("void(0)")'),
            array('#hashtag')
        );
    }


}
