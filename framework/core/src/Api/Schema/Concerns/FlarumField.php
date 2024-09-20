<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Schema\Concerns;

use Flarum\Api\Context;

trait FlarumField
{
    use HasValidationRules;

    /**
     * Allow this field to be written to when creating a new model.
     */
    public function writableOnCreate(): static
    {
        $this->writable = fn ($model, Context $context) => $context->creating();

        return $this;
    }

    /**
     * Allow this field to be written to when updating a model.
     */
    public function writableOnUpdate(): static
    {
        $this->writable = fn ($model, Context $context) => $context->updating();

        return $this;
    }

    public function nullable(bool $nullable = true): static
    {
        $this->nullable = $nullable;

        return $this->rule('nullable');
    }
}
