<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Schema;

class DateTime extends Attribute
{
    public static function make(string $name): static
    {
        return (new static($name))
            ->type(\Tobyz\JsonApiServer\Schema\Type\DateTime::make())
            ->rule('date');
    }

    public function before(string $date, bool|callable $condition = true): static
    {
        return $this->rule('before:'.$date, $condition);
    }

    public function after(string $date, bool|callable $condition = true): static
    {
        return $this->rule('after:'.$date, $condition);
    }

    public function beforeOrEqual(string $date, bool|callable $condition = true): static
    {
        return $this->rule('before_or_equal:'.$date, $condition);
    }

    public function afterOrEqual(string $date, bool|callable $condition = true): static
    {
        return $this->rule('after_or_equal:'.$date, $condition);
    }

    public function format(string $format, bool|callable $condition = true): static
    {
        return $this->rule('date_format:'.$format, $condition);
    }
}
