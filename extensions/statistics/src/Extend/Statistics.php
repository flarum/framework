<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Statistics\Extend;

use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Builder;

/**
 * Adds a statistic to the dashboard widget and the statistics page: a count of
 * records, in total and over time.
 *
 * Wrap it in a `Conditional` so it only applies while flarum/statistics is
 * enabled:
 *
 *     (new Flarum\Extend\Conditional())
 *         ->whenExtensionEnabled('flarum-statistics', fn () => [
 *             (new Flarum\Statistics\Extend\Statistics())
 *                 ->entity('polls', fn () => Poll::query(), 'created_at'),
 *         ]),
 *
 * The statistic is labelled by the translation
 * `flarum-statistics.admin.statistics.{name}_heading`, which the extension
 * adding it provides in its own locale file.
 *
 * Every timed request counts the records in a range of the date column, so
 * that column should be indexed, together with any column the query filters
 * on, or each request reads the whole table.
 */
class Statistics implements ExtenderInterface
{
    /**
     * @var array<string, array{query: callable(): Builder, column: string}>
     */
    protected array $entities = [];

    /**
     * @param string $name A unique name. It is also how the statistic is requested and labelled.
     * @param callable(): Builder $query Returns a fresh query for the records to count.
     * @param string $dateColumn The column dating each record.
     */
    public function entity(string $name, callable $query, string $dateColumn): self
    {
        $this->entities[$name] = ['query' => $query, 'column' => $dateColumn];

        return $this;
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
        if (! $container->bound('flarum-statistics.entities')) {
            $container->instance('flarum-statistics.entities', []);
        }

        $container->extend('flarum-statistics.entities', fn (array $entities) => array_merge($entities, $this->entities));
    }
}
