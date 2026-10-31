<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Usage;

use Carbon\CarbonImmutable;
use Flarum\Ai\AiConfig;
use Flarum\Ai\Exception\LimitReachedException;
use Flarum\Ai\Exception\LimitType;
use Flarum\Ai\Features\FeatureSettings;
use Flarum\Settings\SettingsRepositoryInterface;

/**
 * The token limits an admin can set: per connection each calendar month, per
 * feature each day, per member each day, and for all guests together each
 * day. Days and months are in UTC.
 *
 * A call is stopped once a limit is used up. The check is made before the call
 * and the usage recorded after it, so calls already running when a limit runs
 * out still finish: a limit can be overshot by those calls' tokens.
 *
 * Admins have no member limit. Calls the forum makes for itself, with
 * `asSystem()`, have no member or guest limit.
 *
 * @internal
 */
class Limits
{
    public const MEMBER_DAILY_SETTING = 'flarum-ai.member_daily_tokens';

    public const GUEST_DAILY_SETTING = 'flarum-ai.guest_daily_tokens';

    public function __construct(
        protected Ledger $ledger,
        protected AiConfig $config,
        protected FeatureSettings $features,
        protected SettingsRepositoryInterface $settings
    ) {
    }

    /**
     * @throws LimitReachedException
     */
    public function check(string $connection, ?Context $context): void
    {
        $now = CarbonImmutable::now('UTC');
        $month = $now->startOfMonth();
        $today = $now->startOfDay();

        $monthly = $this->positive($this->config->connections()[$connection]['monthly_tokens'] ?? null);

        if ($monthly !== null && $this->ledger->tokensSince('connection', $connection, $month) >= $monthly) {
            throw new LimitReachedException(LimitType::Connection, $connection, $monthly, $month->addMonth());
        }

        if ($context === null) {
            return;
        }

        $daily = $this->positive($this->features->get($context->feature)['daily_tokens']);

        if ($daily !== null && $this->ledger->tokensSince('feature', $context->feature, $today) >= $daily) {
            throw new LimitReachedException(LimitType::Feature, $context->feature, $daily, $today->addDay());
        }

        switch ($context->actorKind()) {
            case 'member':
                if ($context->actor->isAdmin()) {
                    return;
                }

                $daily = $this->positive($this->settings->get(self::MEMBER_DAILY_SETTING));

                if ($daily !== null && $this->ledger->tokensSince('user_id', $context->userId(), $today) >= $daily) {
                    throw new LimitReachedException(LimitType::Member, $context->actor->username, $daily, $today->addDay());
                }

                return;

            case 'guest':
                $daily = $this->positive($this->settings->get(self::GUEST_DAILY_SETTING));

                if ($daily !== null && $this->ledger->tokensSince('actor', 'guest', $today) >= $daily) {
                    throw new LimitReachedException(LimitType::Guests, null, $daily, $today->addDay());
                }
        }
    }

    protected function positive(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}
