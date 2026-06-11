<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Search\Gambits;

use Flarum\Search\AbstractRegexGambit;
use Flarum\Search\SearchState;
use Flarum\Settings\SettingsRepositoryInterface;

class IpGambit extends AbstractRegexGambit
{
    public function apply(SearchState $search, $bit)
    {
        if (! $search->getActor()->hasPermission('flarum-audit.view')) {
            /** @var SettingsRepositoryInterface $settings */
            $settings = resolve(SettingsRepositoryInterface::class);

            if (! $settings->get('flarum-audit.limitedIpAddress')) {
                return false;
            }
        }

        return parent::apply($search, $bit);
    }

    protected function getGambitPattern(): string
    {
        return 'ip:(.+)';
    }

    protected function conditions(SearchState $search, array $matches, $negate)
    {
        $ipAddresses = explode(',', trim($matches[1], '"'));

        $search->getQuery()->whereIn('ip_address', $ipAddresses, 'and', $negate);
    }
}
