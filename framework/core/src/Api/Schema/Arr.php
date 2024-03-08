<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Schema;

/**
 * @todo validation rules for the array items.
 */
class Arr extends Attribute
{
    public static function make(string $name): static
    {
        return (new static($name))
            ->type(Type\Arr::make())
            ->rule('array');
    }
}
