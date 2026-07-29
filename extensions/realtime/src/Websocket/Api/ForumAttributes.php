<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Api;

use Flarum\Api\Context;
use Flarum\Api\Schema\Arr;
use Flarum\Api\Schema\Boolean;
use Flarum\Api\Schema\Str;
use Flarum\Realtime\Websocket\Settings;
use Flarum\Settings\SettingsRepositoryInterface;

class ForumAttributes
{
    public function __construct(
        protected Settings $settings
    ) {
    }

    public function __invoke(): array
    {
        return [
            Str::make('websocket.key')
                ->get(fn () => $this->settings->appKey),
            Str::make('websocket.host')
                ->get(fn () => $this->settings->jsClientHost),
            Str::make('websocket.port')
                ->get(fn () => $this->settings->jsClientPort),
            Boolean::make('websocket.secure')
                ->get(fn () => $this->settings->jsClientSecure),

            // The restricted tags the actor may see, so the client subscribes to
            // exactly those per-tag index-typing channels instead of one channel
            // (and one auth round-trip) per visible tag. Empty unless the
            // restricted index-typing dots are enabled and Tags is active.
            Arr::make('flarum-realtime.index-typing-tags')
                ->get(fn ($model, Context $context) => $this->indexTypingTagIds($context)),
        ];
    }

    /**
     * @return int[]
     */
    protected function indexTypingTagIds(Context $context): array
    {
        $actor = $context->getActor();

        if ($actor->isGuest() || ! class_exists(\Flarum\Tags\Tag::class)) {
            return [];
        }

        $restrictedEnabled = (bool) resolve(SettingsRepositoryInterface::class)
            ->get('flarum-realtime.index-typing-indicator-restricted');

        if (! $restrictedEnabled) {
            return [];
        }

        return \Flarum\Tags\Tag::whereVisibleTo($actor)
            ->where('is_restricted', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
