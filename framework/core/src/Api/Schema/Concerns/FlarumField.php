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
     * UI hints for the generic admin resource components.
     *
     * Kept apart from `$schema` so that `->schema([...])` cannot wipe them and
     * they never leak into the JSON:API schema output.
     *
     * @var array<string, mixed>
     */
    protected array $ui = [];

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

    /**
     * Attach UI hints for the generic admin resource components. Hints are
     * merged, so several extenders can each add their own keys.
     *
     * - `column`: bool, or int priority, to show the field as a grid column;
     * - `display`: the attribute used to label a related record;
     * - `label`: translation key or literal;
     * - `placeholder`: translation key or literal, for the form input;
     * - `widget`: the form input to use; `hidden` means the field is never offered in a form.
     *
     * Other keys, such as `group` and `priority`, are passed through but not read.
     *
     * @param array<string, mixed> $hints
     */
    public function ui(array $hints): static
    {
        $this->ui = array_merge($this->ui, $hints);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getUi(): array
    {
        return $this->ui;
    }
}
