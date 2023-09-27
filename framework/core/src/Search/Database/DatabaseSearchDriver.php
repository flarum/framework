<?php

namespace Flarum\Search\Database;

use Flarum\Search\AbstractDriver;

class DatabaseSearchDriver extends AbstractDriver
{
    public static function name(): string
    {
        return 'default';
    }
}
