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
 * The layout is an ordinary user preference, so the preference endpoint would
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
        $previous = $original[DeckLayout::PREFERENCE_KEY] ?? null;

        if ($user->getPreference(DeckLayout::PREFERENCE_KEY) !== $previous && $user->cannot('deck.use')) {
            $user->setPreference(DeckLayout::PREFERENCE_KEY, $previous);
        }
    }
}
