<?php
declare(strict_types=1);

namespace kosuha606\HtmlUniParser\action;

use Assert\Assertion;
use kosuha606\HtmlUniParser\BaseObject;
use kosuha606\HtmlUniParser\Factory;

/**
 * Abstract action. Such actions can run as independent modules
 * @package kosuha606\HtmlUniParser\action
 */
abstract class AbstractLogicAction extends BaseObject
{
    /**
     * @var array
     */
    public $args = [];

    /**
     * @throws \Assert\AssertionFailedException
     */
    public static function do()
    {
        $args = func_get_args();
        /** @var static $object */
        $object = Factory::createObject([
            'class' => static::class
        ]);
        $object->args = $args;
        return $object->run();
    }

    /**
     *
     */
    public function run()
    {
        throw new \LogicException('Logic action must implement method run');
    }
}