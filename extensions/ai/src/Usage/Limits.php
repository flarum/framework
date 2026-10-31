<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Usage;

use Carbon\Carbon;
use Flarum\Ai\AiConfig;
use Flarum\Ai\Features\FeatureSettings;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;

/**
 * The token limits an admin can set: per connection each month, per feature
 * each day, and per member each day. A call is stopped once a limit is used
 * up; the call that crosses it is allowed to finish, so a limit can be
 * overshot by one call's worth of tokens.
 */
class Limits
{
    public const MEMBER_DAILY_SETTING = 'flarum-ai.member_daily_tokens';

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
    public function check(string $connection, ?string $feature, ?User $actor): void
    {
        $monthly = $this->positive($this->config->connections()[$connection]['monthly_tokens'] ?? null);

        if ($monthly !== null && $this->ledger->tokensSince('connection', $connection, Carbon::now()->startOfMonth()) >= $monthly) {
            throw new LimitReachedException('connection', $connection, $monthly);
        }

        $today = Carbon::now()->startOfDay();

        if ($feature !== null) {
            $daily = $this->positive($this->features->get($feature)['daily_tokens'] ?? null);

            if ($daily !== null && $this->ledger->tokensSince('feature', $feature, $today) >= $daily) {
                throw new LimitReachedException('feature', $feature, $daily);
            }
        }

        // Admins are trusted with the forum's spend.
        if ($actor !== null && $actor->exists && ! $actor->isAdmin()) {
            $daily = $this->positive($this->settings->get(self::MEMBER_DAILY_SETTING));

            if ($daily !== null && $this->ledger->tokensSince('user_id', $actor->id, $today) >= $daily) {
                throw new LimitReachedException('member', $actor->username, $daily);
            }
        }
    }

    protected function positive(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}
