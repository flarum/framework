<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags;

use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;

class AddFlagsApiAttributes
{
    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {}

    public function __invoke(ForumSerializer $serializer): array
    {
        $attributes = [
            'canViewFlags' => $serializer->getActor()->hasPermissionLike('discussion.viewFlags')
        ];

        if ($attributes['canViewFlags']) {
            $attributes['flagCount'] = (int) $this->getFlagCount($serializer->getActor());
        }

        return $attributes;
    }

    protected function getFlagCount(User $actor): int
    {
        return Flag::whereVisibleTo($actor)->distinct()->count('flags.post_id');
    }
}
