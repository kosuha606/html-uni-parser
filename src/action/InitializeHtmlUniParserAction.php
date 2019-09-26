<?php
declare(strict_types=1);

namespace kosuha606\HtmlUniParser\action;

use Assert\Assertion;
use kosuha606\HtmlUniParser\HtmlUniParser;
use kosuha606\HtmlUniParser\ZendBasedParser;

/**
 * Initialize process
 * @package kosuha606\HtmlUniParser\action
 */
class InitializeHtmlUniParserAction extends AbstractLogicAction
{
    /**
     * @throws \Assert\AssertionFailedException
     */
    public function run()
    {
        /** @var HtmlUniParser $htmlParserInst */
        $htmlParserInst = $this->args[0];
        /** @var ZendBasedParser $zendParserInst */
        $zendParserInst = $this->args[1];
        Assertion::isInstanceOf($htmlParserInst, HtmlUniParser::class);
        Assertion::isInstanceOf($zendParserInst, ZendBasedParser::class);
        $htmlParserInst->setZendParser($zendParserInst);
        $xpathOnCardMany = $xpathOnCardHtml = [];
        if (\count($htmlParserInst->getXpathOnCard()) > 0) {
            $xpathOnCard = $htmlParserInst->getXpathOnCard();
            foreach ($xpathOnCard as $param => &$xpath) {
                if (\strpos($xpath, 'MANY') !== false) {
                    $xpathOnCardMany[] = $param;
                    $xpath = \str_replace('MANY', '', $xpath);
                }
                if (\strpos($xpath, 'HTML') !== false) {
                    $xpathOnCardHtml[] = $param;
                    $xpath = \str_replace('HTML', '', $xpath);
                }
            }
            $htmlParserInst->setXpathOnCard($xpathOnCard);
            $htmlParserInst->setXpathOnCardHtml($xpathOnCardHtml);
            $htmlParserInst->setXpathOnCardMany($xpathOnCardMany);
        }
    }
}