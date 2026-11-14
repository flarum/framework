<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Deck\Listener;

use Flarum\Deck\DeckLayout;
use Flarum\User\Event\Saving;

/**
 * The layout is made of ordinary user preferences, so the preference endpoint would
 * otherwise accept one from anybody. Members without `deck.use` can't change it.
 */
class EnforceDeckPermissions
{
    public function handle(Saving $event): void
    {
        $user = $event->user;

        if (! $user->isDirty('preferences')) {
            return;
        }

        $original = (array) ($user->getOriginal('preferences') ?? []);

        foreach ([DeckLayout::PREFERENCE_KEY, DeckLayout::SPLIT_PREFERENCE_KEY] as $key) {
            $previous = $original[$key] ?? null;

            if ($user->getPreference($key) !== $previous && $user->cannot('deck.use')) {
                $user->setPreference($key, $previous);
            }
        }
    }
}
