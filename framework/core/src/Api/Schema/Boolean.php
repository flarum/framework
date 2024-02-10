<?php

namespace Flarum\Api\Schema;

class Boolean extends Attribute
{
    public static function make(string $name): static
    {
        return (new static($name))
            ->type(\Tobyz\JsonApiServer\Schema\Type\Boolean::make())
            ->rule('boolean');
    }
}
