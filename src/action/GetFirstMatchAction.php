<?php
declare(strict_types=1);

namespace kosuha606\HtmlUniParser\action;

/**
 * @package kosuha606\HtmlUniParser\action
 */
class GetFirstMatchAction extends AbstractLogicAction
{
    /**
     * @return array|void
     */
    public function run()
    {
        $results = $this->args[0];
        $result = array();
        foreach ($results as $r) {
            $result = $r;
            break;
        }

        return $result;
    }
}