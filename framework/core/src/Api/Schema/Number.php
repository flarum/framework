<?php

namespace Flarum\Api\Schema;

class Number extends Attribute
{
    public static function make(string $name): static
    {
        return (new static($name))
            ->type(\Tobyz\JsonApiServer\Schema\Type\Number::make())
            ->rule('numeric');
    }

    public function min(int $min, bool|callable $condition = true): static
    {
        return $this->rule("min:$min", $condition);
    }

    public function max(int $max, bool|callable $condition = true): static
    {
        return $this->rule("max:$max", $condition);
    }
}
