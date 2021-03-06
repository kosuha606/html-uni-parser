<?php

use kosuha606\HtmlUniParser\HtmlUniParser;
use kosuha606\HtmlUniParser\ZendBasedParser;
use PHPUnit\Framework\TestCase;
use Zend\Dom\Query;

/**
 * Class HtmlUniParserTest
 */
class HtmlUniParserTest extends TestCase
{
    public function testParseCard()
    {
        $parser = $this
            ->getMockBuilder(ZendBasedParser::class)
            ->getMock()
        ;
        $parser->expects($this->any())->method('dom')->will($this->returnValue(
            new Query(file_get_contents(__DIR__.'/../data/test_parse_card.html'))
        ));
        $result = (new HtmlUniParser([
            'pageUrl' => 'test_card',
            'xpathOnCard' => [
                'h1' => '//h1',
                'description' => 'HTML//p'
            ]
        ], $parser))->parseCard();
        $this->assertEquals('Загловок страницы', $result['h1']);
        $this->assertEquals('<p>Краткое описание</p>', $result['description']);
    }

    public function testParseUrl()
    {
        $parser = $this
            ->getMockBuilder(ZendBasedParser::class)
            ->getMock()
        ;
        $parser->expects($this->any())->method('dom')->will($this->returnValue(
            new Query(file_get_contents(__DIR__.'/../data/test_parse_url.html'))
        ));
        $result = (new HtmlUniParser([
            'catalogUrl' => 'test_card',
            'xpathItem' => '//ul/li',
            'xpathLink' => '//a/@href',
            'xpathTitle' => '//a',
            'goIntoCard' => true,
            'xpathOnCard' => [
                'h1' => '//h2',
                'description' => '//div[@class="page-wrapper"]'
            ]
        ], $parser))->parseUrl();
        $this->assertEquals(3, count($result));
        $this->assertEquals('Page title', $result[0]['h1']);
    }

    public function testEncodingCP1251()
    {
        $this->markTestSkipped('in develop');
        $parser = $this
            ->getMockBuilder(ZendBasedParser::class)
            ->getMock()
        ;
        $content = '<meta charset="UTF-8" />'.file_get_contents(__DIR__.'/../data/cp1251_encoding.html');
        //$content = iconv('CP1251', 'UTF-8', $content);
        $parser->expects($this->any())->method('dom')->will($this->returnValue(
            new Query($content, 'Windows-1251')
        ));
        $result = (new HtmlUniParser([
            'pageUrl' => 'test_card',
            'encoding' => 'Windows-1251',
            'xpathOnCard' => [
                'h1' => '//h1',
            ]
        ], $parser))->parseCard();
        $this->assertEquals('Кирилица в заголовке', $result['h1']);
    }

    public function testParseSearch()
    {
        $this->markTestSkipped('in develop');
    }

    public function testParseGenerator()
    {
        $this->markTestSkipped('in develop');
    }
}
