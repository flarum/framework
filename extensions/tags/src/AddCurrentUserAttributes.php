<?php

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
