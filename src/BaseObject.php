<?php

namespace kosuha606\HtmlUniParser;

/**
 * Class BaseObject
 * Configurable class
 * @package app\Parsers
 */
class BaseObject
{
    /**
     * BaseObject constructor.
     * @param $config
     */
    public function __construct($config)
    {
        foreach ($config as $key => $value) {
            if (\property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}
