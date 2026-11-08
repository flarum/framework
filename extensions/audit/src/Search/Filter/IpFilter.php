<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Search\Filter;

use Flarum\Audit\IpRange;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class IpFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'ip';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        // Mirror the permission gate that previously lived in IpGambit::apply():
        // actors without full view permission may only search by IP when the
        // limitedIpAddress setting is enabled. Otherwise the filter is a no-op.
        if (! $state->getActor()->hasPermission('flarum-audit.view')) {
            /** @var SettingsRepositoryInterface $settings */
            $settings = resolve(SettingsRepositoryInterface::class);

            if (! $settings->get('flarum-audit.limitedIpAddress')) {
                return;
            }
        }

        $values = array_map(trim(...), $this->asStringArray($value));

        $matchesAny = function (Builder $query) use ($values) {
            foreach ($values as $value) {
                $query->orWhere(fn (Builder $query) => $this->matches($query, $value));
            }
        };

        if ($negate) {
            $state->getQuery()->whereNot($matchesAny);
        } else {
            $state->getQuery()->where($matchesAny);
        }
    }

    /**
     * One value of the filter: an address, a CIDR range or a partial address
     * (see IpRange::parse()), matched on `ip_hex`.
     *
     * Every comparison is guarded with `IS NOT NULL`, so it is true or false,
     * never SQL NULL: `NOT (NULL)` is NULL, so a negated filter would otherwise
     * also drop every entry with no IP address, such as console actions, which
     * did not come from the excluded address.
     */
    protected function matches(Builder $query, string $value): void
    {
        $range = IpRange::parse($value);

        if ($range !== null) {
            $query->where(fn (Builder $query) => $query
                ->whereNotNull('ip_hex')
                ->whereBetween('ip_hex', $range));
        }

        // An exact value is also compared as written, which still finds the
        // entries logged before `ip_hex` existed and not yet backfilled by
        // `audit:backfill-ip`, and anything stored that isn't an IP address.
        if ($range === null || $range[0] === $range[1]) {
            $query->orWhere(fn (Builder $query) => $query
                ->whereNotNull('ip_address')
                ->where('ip_address', $value));
        }
    }
}
