<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Schema;

class Str extends Attribute
{
    public static function make(string $name): static
    {
        return (new static($name))
            ->type(\Tobyz\JsonApiServer\Schema\Type\Str::make())
            ->rule('string');
    }

    public function minLength(int $length, bool|callable $condition = true): static
    {
        return $this->rule('min:'.$length, $condition);
    }

    public function maxLength(int $length, bool|callable $condition = true): static
    {
        return $this->rule('max:'.$length, $condition);
    }

    public function email(array $validators = [], bool|callable $condition = true): static
    {
        $validators = implode(',', $validators);

        if (! empty($validators)) {
            $validators = ':'.$validators;
        }

        return $this->rule("email$validators", $condition);
    }

    public function regex(string $pattern, bool|callable $condition = true): static
    {
        return $this->rule("regex:$pattern", $condition);
    }
}
