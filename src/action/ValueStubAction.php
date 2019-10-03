<?php

namespace kosuha606\HtmlUniParser\action;

class ValueStubAction extends AbstractLogicAction
{
    public function run()
    {
        $object = $this->args[0];
        $method = $this->args[1];
        if (!\is_object($object)) {
            return null;
        }
        if (\property_exists($object, $method)) {
            return $object->{$method};
        }

        return null;
    }
}