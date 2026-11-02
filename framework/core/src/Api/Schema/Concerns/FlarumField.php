<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Schema\Concerns;

use Closure;
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
     * The value passed to `default()` when it was not a closure; json-api-server wraps
     * every default in a closure, so this is the only record that it was static.
     */
    protected mixed $staticDefault = null;

    protected bool $hasStaticDefault = false;

    public function default(mixed $default): static
    {
        $this->hasStaticDefault = ! $default instanceof Closure;
        $this->staticDefault = $this->hasStaticDefault ? $default : null;

        return parent::default($default);
    }

    public function hasStaticDefault(): bool
    {
        return $this->hasStaticDefault;
    }

    public function getStaticDefault(): mixed
    {
        return $this->staticDefault;
    }

    public function writableOnCreate(): static
    {
        $this->writable = fn ($model, Context $context) => $context->creating();

        return $this;
    }

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
     * - `widget`: the form input to use; `hidden` means the field is never offered in a form;
     * - `help`: translation key or literal, shown under the form input;
     * - `hidden`: the column exists but is hidden until the user shows it;
     * - `group`: translation key or literal; fields of a group share a fieldset;
     * - `priority`: int, the field's position in the form, higher first;
     * - `visibleWhen`: conditions (as `requiredWhen`) under which the form input is shown;
     * - `markRequired`: show the field as required without enforcing it on the client.
     *
     * The typed methods below (`column()`, `widget()`, ...) write the same hints.
     * Other keys are passed through, and logged in debug mode.
     *
     * @param array<string, mixed> $hints
     */
    public function ui(array $hints): static
    {
        $this->ui = array_merge($this->ui, $hints);

        return $this;
    }

    /**
     * An int is the column's priority, higher first.
     */
    public function column(bool|int $column = true): static
    {
        return $this->ui(['column' => $column]);
    }

    /**
     * Offer the field as a column that is hidden until the user shows it.
     */
    public function columnHidden(bool|int $column = true): static
    {
        return $this->ui(['column' => $column, 'hidden' => true]);
    }

    /**
     * The form input to use; `hidden` keeps the field out of forms.
     */
    public function widget(string $widget): static
    {
        return $this->ui(['widget' => $widget]);
    }

    /**
     * @param string $placeholder translation key or literal
     */
    public function placeholder(string $placeholder): static
    {
        return $this->ui(['placeholder' => $placeholder]);
    }

    /**
     * @param string $help translation key or literal, shown under the form input
     */
    public function help(string $help): static
    {
        return $this->ui(['help' => $help]);
    }

    /**
     * @param string $group translation key or literal; fields of a group share a fieldset
     */
    public function group(string $group): static
    {
        return $this->ui(['group' => $group]);
    }

    /**
     * The field's position in the form, higher first.
     */
    public function priority(int $priority): static
    {
        return $this->ui(['priority' => $priority]);
    }

    /**
     * The attribute of a related record that labels it.
     */
    public function display(string $attribute): static
    {
        return $this->ui(['display' => $attribute]);
    }

    /**
     * Show the form input only when any of the conditions holds against the form's values.
     *
     * Conditions take the `requiredWhen` shape (`['rule' => 'if', 'fields' => ['type'], 'values' => ['developer']]`),
     * or a Laravel-style shorthand: `'if:type,developer'`, `'with:email'`, `'without_all:a,b'`.
     *
     * @param string|array<mixed> $conditions
     */
    public function visibleWhen(string|array $conditions): static
    {
        return $this->ui(['visibleWhen' => $conditions]);
    }

    /**
     * Mark the field required in forms without enforcing it on the client,
     * for requirements only the server can decide.
     */
    public function markRequired(bool $markRequired = true): static
    {
        return $this->ui(['markRequired' => $markRequired]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getUi(): array
    {
        return $this->ui;
    }
}
