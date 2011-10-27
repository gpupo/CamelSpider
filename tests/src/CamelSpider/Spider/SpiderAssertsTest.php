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
        $array = array(
            array('Something in the way she moves', array('way')),
            array('Attracts me like no other lover', array('lover')),
            array('Something in the way she woos me',array('something')),
            array('I dont want to leave her now', array('other', 'want')),
        );

        //words
        foreach (explode(' ', $this->getBigText()) as $word) {
            $array[] = array($this->getBigText(),array('constituinte', 'firebug', 'metallica', $word));
        }

        //half words
        foreach (explode(' ', 'cordos teresse mum rust niciati cida') as $word) {
            $array[] = array($this->getBigText(),array('constituinte', 'firebug', 'metallica', $word));
        }

        return $array;
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

    private function getBigText()
    {
       return <<<EOF
O documento foi assinado pelo presidente da Fapesp, Celso Lafer, e pelo diretor da GSK para a América Latina e o Caribe, Rogério Rocha Ribeiro. A cerimônia teve ainda a participação do ministro da Saúde do Reino Unido, Simon Burns, do diretor-presidente da Fapesp, Ricardo Renzo Brentani, e do cônsul-geral britânico, John Dodrell.
A colaboração foi estabelecida no âmbito do Projeto Trust in Science, iniciativa internacional do laboratório que também envolve, no país, o Conselho Nacional de Desenvolvimento Científico e Tecnológico (CNPq). 
"Há alguns anos a FAPESP se empenha na dimensão da internacionalização, seja por meio do aumento das cooperações com o setor privado, seja a partir de acordos de interesse comum entre nações. Este documento se insere nesse esforço e prevê o estabelecimento de mecanismos de apoio a projetos de interesse mútuo para o avanço do conhecimento sobre doenças tropicais relevantes para a saúde pública no Brasil e no mundo", afirmou Lafer.
Ribeiro afirmou que a GSK identificou algumas áreas terapêuticas de interesse que poderão ser consideradas futuramente nas chamadas de propostas. "As principais áreas de interesses da GSK são as doenças negligenciadas, doenças crônicas, doenças metabólicas, diabetes e doenças respiratórias. A ideia é abrir ainda mais esse leque", disse.
De acordo com ele, o objetivo é apoiar a pesquisa acadêmica em projetos de ciência aplicada, que eventualmente levem ao desenvolvimento de medicamentos, vacinas e produtos, que possam ter grande impacto na saúde pública.
"Nosso sonho é que essa cooperação nos leve, por exemplo, a alguma nova molécula que tenha impacto não só no Brasil e na América Latina, mas em todo o mundo, fazendo com que a pesquisa brasileira beneficie todo o planeta", disse.
O documento prevê que a GSK será responsável por dar apoio financeiro, especificar áreas temáticas de interesse para empresa e irá cooperar com a Fapesp na publicação de chamadas de propostas. O laboratório oferecerá ainda contribuição técnica para o trabalho e se dispõe a realizar esforços para construir boas relações de longo prazo com a comunidade paulista de pesquisa.
A Fapesp será responsável por fornecer suporte financeiro em proporção igual às contribuições da GSK e cooperar com o laboratório na seleção de propostas de pesquisa na regulamentação de direitos de propriedade intelectual. A Fundação também organizará o processo de chamadas de propostas e a administrará os financiamentos para os pesquisadores responsáveis pelos projetos aprovados, além de acompanhar os relatórios de resultados.
EOF;

    }


}
