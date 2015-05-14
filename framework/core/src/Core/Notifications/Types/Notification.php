<?php namespace Flarum\Core\Notifications\Types;

abstract class Notification
{
    abstract public static function getType();
}
