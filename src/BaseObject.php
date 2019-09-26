<?php
declare(strict_types=1);

namespace kosuha606\HtmlUniParser;

/**
 * Configurable class
 * @package app\Parsers
 */
abstract class BaseObject
{
    /**
     * @param $config
     */
    public function __construct($config = [])
    {
        foreach ($config as $key => $value) {
            if (\property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}
