<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages;

use Flarum\Settings\SettingsRepositoryInterface;

/**
 * How many members one message can start a conversation with. More than one
 * makes it a group. One turns groups off. The ceiling bounds how many emails
 * a single message can send, and how many members a conversation's lists load.
 */
class RecipientLimit
{
    public const SETTING = 'flarum-messages.max_recipients';

    public const DEFAULT = 10;

    public const CEILING = 50;

    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function get(): int
    {
        return self::clamp($this->settings->get(self::SETTING));
    }

    public static function clamp(mixed $value): int
    {
        $limit = is_numeric($value) ? (int) $value : self::DEFAULT;

        return max(1, min(self::CEILING, $limit));
    }
}
