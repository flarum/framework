<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\GitHub;

use Flarum\Settings\SettingsRepositoryInterface;

/**
 * The GitHub integration's settings. All of it is off until an administrator
 * switches it on, and the token and secret never leave the server (the
 * admin page only learns whether each is saved, see Secrets).
 */
class Config
{
    public const PREFIX = 'flarum-ideation.github.';

    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function enabled(): bool
    {
        return (bool) $this->settings->get(self::PREFIX.'enabled');
    }

    public function token(): string
    {
        return trim((string) $this->settings->get(self::PREFIX.'token'));
    }

    public function secret(): string
    {
        return (string) $this->settings->get(self::PREFIX.'secret');
    }

    public function autoProgress(): bool
    {
        return (bool) $this->settings->get(self::PREFIX.'auto_progress');
    }

    public function autoShip(): bool
    {
        return (bool) $this->settings->get(self::PREFIX.'auto_ship');
    }

    /**
     * Only repositories on the list are linked, shown, refreshed or allowed
     * to move an idea. With a token, the forum can read private
     * repositories, and a link shows its title to everyone who can read the
     * idea; the list is what keeps a moderator from pointing the owner's
     * token at something private.
     *
     * Entries are `owner/repo` or `owner/*`, separated by commas, spaces or
     * new lines.
     */
    public function allows(string $repo): bool
    {
        $repo = strtolower($repo);
        [$owner] = explode('/', $repo);

        foreach (preg_split('/[\s,]+/', strtolower((string) $this->settings->get(self::PREFIX.'repos')), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $entry) {
            if ($entry === $repo || $entry === $owner.'/*') {
                return true;
            }
        }

        return false;
    }
}
