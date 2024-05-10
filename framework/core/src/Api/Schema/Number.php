<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Schema;

use Flarum\Api\Schema\Concerns\GetsRelationAggregates;
use Flarum\Api\Schema\Contracts\RelationAggregator;

class Number extends Attribute implements RelationAggregator
{
    use GetsRelationAggregates;

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
