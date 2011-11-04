<?php

namespace CamelSpider\Spider;

class SpiderDomTest extends \PHPUnit_Framework_TestCase {

    /**
     * @dataProvider providerDomElements
     */
    public function testToHtml(\DOMNode $node, $html)
    {
       $this->AssertEquals($html, SpiderDom::toHtml($node));
    }

    /**
     * @dataProvider providerHtmlElements()
     */
    public function testToText($html, $text)
    {
        $this->AssertEquals($text, SpiderDom::htmlToText($html));
    }

    /**
     * @dataProvider providerHtmlStories()
     */
    public function testHtmlToIntro($html, $text)
    {
        foreach (array(4, strlen($text)) as $i) {
           $this->AssertEquals(trim(mb_substr($text, 0, $i)), SpiderDom::htmlToIntro($html, $i));
           $this->AssertEquals(trim(mb_substr($text, 0, $i)) . (strlen($text) > $i ? '...' : ''), SpiderDom::htmlToIntro($html, $i, '...'));
        }
    }

    public function providerHtmlStories()
    {
        $a = array();
        foreach (array('text example', 'Word sample for test', 'floo fly flo fi', 'boot for both') as $t) {
            $a = array_merge($a, $this->makeHtmlElements($t));
        }
        return $a;
    }


    public function providerHtmlElements()
    {
        $a = array();
        foreach (array('text example', 'other example', 'some text', 'lets play') as $t) {
            $a = array_merge($a, $this->makeHtmlElements($t));
        }
        return $a;
    }

    public function makeHtmlElements($txt)
    {
        $html = $txt ;
        $a = array();
        foreach(SpiderDom::$stripedTags as $e) {
            $html = '<' . $e . '>'. $html . '</' . $e . '>' . "\n";
            $a[] = array($html , $txt);
        }
        return $a;
    }

    public function providerDomElements()
    {
        $array = array();
        foreach ($this->getHtmlCollection() as $html) {
            $html = trim($html);
            if (!empty($html)) {
                $doc = $this->getDoc($html);
                $expectedHtml = $this->getHtmlExpected($html);
                $array[] = array($doc, $expectedHtml);
                $array[] = array($doc->documentElement, $expectedHtml);
            }
        }
        return $array;
    }

    public function getHtmlExpected($html)
    {
        foreach (array('body','html') as $tag) {

            if (stripos($html, '<' . $tag) === false) {
                $html = '<' . $tag . '>'. $html . '</'. $tag .'>';
            }
        }
        return $html;
    }

    public function getHtmlCollection()
    {
        return explode(PHP_EOL, <<<EOF

<html><body>Test<br/></body></html>
<p>Test<br/></p>
<body><p>Test</p></body>
<span>test</span>
<a>a</a>
<em>em</em>

EOF
        );
    }

    public function getDoc($html){
        $doc = new \DOMDocument();
        $doc->loadHTML($html);

        return $doc;
    }
}
