<?php

namespace kosuha606\HtmlUniParser\adapters\yii;

/**
 * This is an adaptor to use in Yii2 application
 * Class HtmlUniParser
 * @package kosuha606\HtmlUniParser\adapters\yii
 */
class HtmlUniParser extends yii\Base\Component
{
    /** @var \kosuha606\HtmlUniParser\HtmlUniParser  */
    private $parser;

    /**
     * HtmlUniParser constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->parser = \kosuha606\HtmlUniParser\HtmlUniParser::create($config);
    }

    /**
     * @param mixed $callbacs
     */
    public function addCallback($callbacs)
    {
        $this->parser->addCallback($callbacs);
    }

    public function parseUrl()
    {
        return $this->parser->parseUrl();
    }

    public function parseSearch($textQuery)
    {
        return $this->parser->parseSearch($textQuery);
    }

    public function parseCard()
    {
        return $this->parser->parseCard();
    }

    public function parseGenerator()
    {
        return $this->parser->parseGenerator();
    }

    public function parseGeneratorUrl()
    {
        // TODO implement
    }
}