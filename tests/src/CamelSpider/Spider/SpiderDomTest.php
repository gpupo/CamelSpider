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
     * @dataProvider providerDomElements
     */
    public function testToCleanHtml(\DOMNode $node, $html)
    {
       $this->AssertEquals($html, SpiderDom::toCleanHtml($node));
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

    /** 
     * @dataProvider providerDirtyTags()
     */
    public function testRemoveDirtyAttrs($dirty, $expected)
    {
        $this->AssertEquals($expected, SpiderDom::removeDirtyAttrs($dirty));
    }

    /** 
     * @dataProvider providerCleanTags()
     */
    public function testNotRemoveDirtyAttrs($clean)
    {
        $this->AssertEquals($clean, SpiderDom::removeDirtyAttrs($clean));
    }

    /** 
     * @dataProvider providerTrashTags()
     */
    public function testRemoveTrashBlock($block, $expected)
    {
        $this->AssertEquals($expected, trim(SpiderDom::removeTrashBlock($block)));
    }



    public function providerTrashTags()
    {
        return array(
            array('Some 
                <script language="javascript" type="text/javascript"><![CDATA[
                // krux kseg and kuid from krux header tag
                ]]></script>','Some'),
            array('Some <iframe src="about:blank" id="cnnusercomment" name="cnnusercomment"
                marginheight="0" marginwidth="0" style="position: absolute; bottom: 0pt; left:
                0pt;" width="1" scrolling="no" frameborder="0" height="1"/>', 'Some'),
            array('Text<style type="text/css">.fake{}</style>', 'Text'),
            array('Text<noscript>Trash</noscript>', 'Text')

        );
    }

    public function providerDirtyTags()
    {

        return array(
            array('<div class="Newstime" 
                oncontextmenu="return false"
                ondragstart="return false"
                onselectstart="return false" 
                onselect="document.selection.empty()" 
                oncopy="document.selection.empty()" 
                onbeforecopy="return false">', '<div class="Newstime">'),
            array('<div onclick="something">Some</div>', '<div>Some</div>')
        );
    }
    public function providerCleanTags()
    {
        return array(
            array('<a href="#true">True Link</a>'),
            array('<p style="color:#000">Text</p>'), 
        );
    }

    public function providerHtmlStories()
    {
        $a = array();
        foreach (array(
            'text example',
            'Word sample for test',
            'floo fly flo fi',
            'boot for both',
            'fail A estrutura de um shopping em construção desabou e atingiu o auditório da Universidade Metodista'
            )
            as $t) {
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
