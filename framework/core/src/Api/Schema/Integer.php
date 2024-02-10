<?php

namespace Flarum\Api\Schema;

class Integer extends Number
{
    public static function make(string $name): static
    {
        return (new static($name))
            ->type(\Tobyz\JsonApiServer\Schema\Type\Integer::make())
            ->rule('integer');
    }
}
