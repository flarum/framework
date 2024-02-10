<?php

namespace Flarum\Api\Schema;

class Date extends DateTime
{
    public static function make(string $name): static
    {
        return (new static($name))
            ->type(\Tobyz\JsonApiServer\Schema\Type\Date::make())
            ->rule('date');
    }
}
