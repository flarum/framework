<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Search\Filter;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;
use Flarum\Settings\SettingsRepositoryInterface;

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

        $ipAddresses = $this->asStringArray($value);

        $state->getQuery()->whereIn('ip_address', $ipAddresses, 'and', $negate);
    }
}
