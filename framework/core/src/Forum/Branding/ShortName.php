<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Branding;

use Flarum\Settings\SettingsRepositoryInterface;

/**
 * The forum's name where its full title doesn't fit: a browser's list of
 * search engines, or under an icon on a phone's home screen.
 */
class ShortName
{
    public const MAX_LENGTH = 16;

    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    /**
     * The short name an admin set, if any.
     */
    public function setting(): ?string
    {
        $name = trim((string) $this->settings->get('forum_short_name'));

        return $name === '' ? null : $name;
    }

    /**
     * The short name, or else the forum title, cut at the last whole word
     * that fits when it is too long.
     */
    public function get(): string
    {
        $name = $this->setting() ?? trim((string) $this->settings->get('forum_title'));

        if (mb_strlen($name) <= self::MAX_LENGTH) {
            return $name;
        }

        $cut = mb_substr($name, 0, self::MAX_LENGTH + 1);
        $space = mb_strrpos($cut, ' ');

        return rtrim($space ? mb_substr($cut, 0, $space) : mb_substr($cut, 0, self::MAX_LENGTH));
    }
}
