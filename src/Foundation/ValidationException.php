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
    protected $attributes;
    protected $relationships;

    public function __construct(array $attributes, array $relationships = [])
    {
        $this->attributes = $attributes;
        $this->relationships = $relationships;

        $messages = [implode("\n", $attributes), implode("\n", $relationships)];

        parent::__construct(implode("\n", $messages));
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getRelationships()
    {
        return $this->relationships;
    }
}
