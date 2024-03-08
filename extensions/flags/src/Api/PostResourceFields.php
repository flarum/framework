<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Api;

use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;

class PostResourceFields
{
    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function __invoke(): array
    {
        return [
            Schema\Boolean::make('canFlag')
                ->get(function (Post $post, Context $context) {
                    $actor = $context->getActor();

                    return $actor->can('flag', $post) && (
                        // $actor is not the post author
                        $actor->id !== $post->user_id
                        // If $actor is the post author, check to see if the setting is enabled
                        || ((bool) $this->settings->get('flarum-flags.can_flag_own'))
                    );
                }),
            Schema\Relationship\ToMany::make('flags')
                ->includable(),
        ];
    }
}
