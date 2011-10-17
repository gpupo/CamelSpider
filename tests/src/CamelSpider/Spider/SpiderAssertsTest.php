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

    /**
     * @dataProvider providerContainKeywords
     */
    public function testContainKeywords($txt, $word)
    {
        $this->assertTrue(SpiderAsserts::containKeywords($txt, $word, true));
    }

    /**
     * @dataProvider providerNotContainKeywords
     */
    public function testNotContainKeywords($txt, $word)
    {
        $this->assertFalse(SpiderAsserts::containKeywords($txt, $word, false));
    }

    /**
     * @dataProvider providerContainKeywords
     */
    public function testContainBadKeywords($txt, $word)
    {
        $this->assertTrue(SpiderAsserts::containKeywords($txt, $word, false));
    }

    public function testContainNull()
    {
        $this->assertTrue(SpiderAsserts::containKeywords('Somewhere in her smile she knows', null));
        $this->assertTrue(SpiderAsserts::containKeywords('Somewhere in her smile she knows', array()));
        $this->assertFalse(SpiderAsserts::containKeywords('Somewhere in her smile she knows', null, false));
        $this->assertFalse(SpiderAsserts::containKeywords('Somewhere in her smile she knows', array(), false));
    }

    public function providerContainKeywords()
    {
        return array(
            array('Something in the way she moves', array('way')),
            array('Attracts me like no other lover', array('lover')),
            array('Something in the way she woos me',array('something')),
            array('I dont want to leave her now', array('other', 'want'))
        );
    }

    public function providerNotContainKeywords()
    {
        return array(
            array('Something in the way she moves', array('love', 'sex')),
            array('Attracts me like no other lover', array('bullet', 'gun')),
            array('Something in the way she woos me',array('route', 'bad')),
            array('I dont want to leave her now', array('other', 'past')),
            array('You know I believe and how', array()),
        );
    }

}
