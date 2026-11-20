<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search\Filter;

use Flarum\User\User;

/**
 * Implemented by filters that can describe themselves to the generic admin components.
 */
interface DescribesFilter
{
    /**
     * Describe the filter to the given actor.
     *
     * Return null to hide the filter, for example when `filter()` ignores the
     * value for this actor: mirror the permission check `filter()` makes.
     *
     * - `kind`: how the value is entered;
     * - `label`: translation key or literal; defaults to the same-named field's
     *   label, else `core.admin.resource.{type}.filters.{snake_key}`;
     * - `schema`: a JSON Schema fragment of one value (`['type' => 'string', 'format' => 'date']`);
     * - `enum` and `enumLabels` (value => translation key or literal);
     * - `related`: the related resource type of a `relationship` filter;
     * - `multiple`: the value is a comma separated list;
     * - `range`: a `date`/`number` value is `from..to`, either side may be empty
     *   (see `RangeValue` and `FiltersByRange`);
     * - `required`: always applied; `default`: the value when none is given;
     * - `negatable`: `-key` negates the filter.
     *
     * @return array{kind: 'string'|'boolean'|'enum'|'relationship'|'date'|'number', label?: string, schema?: array<string, mixed>, enum?: list<string>, enumLabels?: array<string, string>, related?: string, multiple?: bool, range?: bool, required?: bool, default?: string, negatable?: bool}|null
     */
    public function describe(User $actor): ?array;
}
