<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Exception;

class ValidationException extends Exception
{
    public function __construct(
        protected array $attributes,
        protected array $relationships = []
    ) {
        $messages = [implode("\n", $attributes), implode("\n", $relationships)];

        parent::__construct(implode("\n", $messages));
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getRelationships(): array
    {
        return $this->relationships;
    }
}
