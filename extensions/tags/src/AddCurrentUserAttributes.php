<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags;

use Flarum\Api\Serializer\CurrentUserSerializer;
use Flarum\Extension\ExtensionManager;
use Flarum\User\User;

class AddCurrentUserAttributes
{
    /**
     * @var ExtensionManager
     */
    protected $extensions;

    public function __construct(ExtensionManager $extensions)
    {
        $this->extensions = $extensions;
    }

    public function __invoke(CurrentUserSerializer $serializer, User $user, array $attributes): array
    {
        if ($this->extensions->isEnabled('flarum-mentions')) {
            $attributes['canMentionTags'] = $user->can('mentionTags');
        }

        return $attributes;
    }
}
