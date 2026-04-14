<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Extend;

use Flarum\Extension\Extension;
use Illuminate\Contracts\Container\Container;
use Flarum\Extend\ExtenderInterface;

class Subscription implements ExtenderInterface
{
    private array $types = [];

    /**
     * Register additional subscription type aliases accepted by the subscription filter.
     *
     * The canonical value is the string stored in the `discussion_user.subscription`
     * column. The aliases are the surface-level values the frontend may send as the
     * `filter[subscription]` parameter — typically the canonical value itself plus
     * any locale-neutral keywords you want to accept (e.g. verb forms).
     *
     * Example — adding a "lurk" subscription type:
     *
     * ```php
     * (new Extend\Subscription())
     *     ->addSubscriptionType('lurk', ['lurk', 'lurking', 'lurked'])
     * ```
     *
     * @param string          $canonicalValue The value stored in the database.
     * @param string|string[] $aliases        One or more filter values that map to this canonical value.
     */
    public function addSubscriptionType(string $canonicalValue, string|array $aliases): self
    {
        $this->types[$canonicalValue] = array_merge(
            $this->types[$canonicalValue] ?? [],
            (array) $aliases,
        );

        return $this;
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
        // Ensure the registry exists before extending it.
        $container->bindIf('flarum-subscriptions.subscription_types', fn () => []);

        $container->extend('flarum-subscriptions.subscription_types', function (array $types) {
            foreach ($this->types as $canonical => $aliases) {
                $types[$canonical] = array_unique(array_merge($types[$canonical] ?? [], $aliases));
            }

            return $types;
        });
    }
}
