<?php
declare(strict_types=1);

namespace kosuha606\HtmlUniParser\action;

use kosuha606\HtmlUniParser\HtmlUniParser;

/**
 * @package kosuha606\HtmlUniParser\action
 */
class ComposeHtmlAction extends AbstractLogicAction
{
    /**
     * @return mixed|string|void
     */
    public function run()
    {
        /** @var HtmlUniParser $htmlParserInst */
        $htmlParserInst = $this->args[0];
        $node = $this->args[1];
        if ($htmlParserInst->isForceOuterHtml()) {
            return $htmlParserInst->queryOuterHtml($node);
        }
        $innerHTML = '';
        $children = $node->childNodes;
        foreach ($children as $child) {
            $innerHTML .= $child->ownerDocument->saveXML($child);
        }

        return $innerHTML;
    }
}