<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Deck\Api;

use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\Deck\DeckLayout;
use Flarum\Settings\SettingsRepositoryInterface;

class ForumResourceFields
{
    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function __invoke(): array
    {
        return [
            Schema\Boolean::make('canUseDeck')
                ->get(fn (object $model, Context $context) => $context->getActor()->can('deck.use')),

            Schema\Integer::make('deckPollInterval')
                ->visible(fn (object $model, Context $context) => $context->getActor()->can('deck.use'))
                ->get(fn () => DeckLayout::pollInterval($this->settings)),

            Schema\Integer::make('deckMaxColumns')
                ->visible(fn (object $model, Context $context) => $context->getActor()->can('deck.use'))
                ->get(fn () => DeckLayout::maxColumns($this->settings)),
        ];
    }
}
