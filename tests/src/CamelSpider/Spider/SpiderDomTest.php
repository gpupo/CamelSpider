<?php

namespace CamelSpider\Spider;

class SpiderDomTest extends \PHPUnit_Framework_TestCase {



    /**
     * @testdox Fail if wrong html is passed to convert HTML to DomElement
     * @expectedException \Exception
     * @group helper
     * @group dom
     * @dataProvider providerWrongHtml
     */
    public function testHtmToDomElementFail($wrongHtml)
    {
        return SpiderDom::htmlToDomElement($wrongHtml);
    }


    /**
     * @dataProvider providerHtmlElements
     */
    public function testOkIfValidHtmlIsPassed($text, $html)
    {
        $domElement = SpiderDom::htmlToDomElement($html);
        $this->AssertTrue($domElement instanceof \DomElement);
    }



    /**
     * @group helper
     * @group dom
     * @dataProvider providerDomElements
     */
    public function testOkIfDomElementIsConvertedToHtml(\DOMNode $node, $html)
    {
       $this->AssertEquals($html, SpiderDom::toHtml($node));
    }

    /**
     * @group helper
     * @dataProvider providerDomElements
     */
    public function testOkIfCleanHtmlisRigth(\DOMNode $node, $html)
    {
        $this->AssertEquals($html, SpiderDom::toCleanHtml($node));
    }


    /**
     * @dataProvider providerDomElementsToText
     */
    public function testOkIfTextLenIsRight(\DOMNode $node, $len)
    {
        $this->AssertEquals($len, SpiderDom::textLen($node));
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

    public function providerWrongHtml()
    {
        return array(
            array('</html>wrong'),
            array('</body>html'),
            array('</span>to'),
            array('</div>test')
        );
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
        foreach (SpiderDom::$stripedTags as $e) {
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


    public function providerDomElementsToText()
    {
        $array = array();
        foreach (array('some', 'text', 'to', 'test') as $text) {

            $len = mb_strlen($text);
            $html = <<<EOF
<html>
    <head>
        <title>Camel Spider Spider Dom Test</title>
    </head>
    <body>
    <script type="text/javascript">
          var _gaq = _gaq || [];
          _gaq.push(['_setAccount', 'UA-8935ss']);
          _gaq.push(['_setDomainName', '.gpupo.com']);
          _gaq.push(['_trackPageview']);
          (function() {
            var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
            ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
            var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
          })();
    </script>

EOF;
            $html .= $text;
            $html .= <<<EOF
  </body>
</html>
EOF;

            $doc = $this->getDoc($html);
            $array[] = array($doc, $len);
            $array[] = array($doc->documentElement, $len);
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
