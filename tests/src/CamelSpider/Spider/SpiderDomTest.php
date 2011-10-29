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
