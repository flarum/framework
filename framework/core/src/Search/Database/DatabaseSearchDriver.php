<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search\Database;

use Flarum\Search\AbstractDriver;

class DatabaseSearchDriver extends AbstractDriver
{
    public static function name(): string
    {
        return 'default';
    }
}
