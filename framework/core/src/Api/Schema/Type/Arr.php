<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Schema\Type;

use Tobyz\JsonApiServer\Schema\Type\Type;

class Arr implements Type
{
    public static function make(): static
    {
        return new static();
    }

    public function serialize(mixed $value): array
    {
        return (array) $value;
    }

    public function deserialize(mixed $value): array
    {
        return (array) $value;
    }

    public function validate(mixed $value, callable $fail): void
    {
        if (! is_array($value)) {
            $fail('must be an array');
        }
    }

    public function schema(): array
    {
        return [
            'type' => 'array',
        ];
    }
}
