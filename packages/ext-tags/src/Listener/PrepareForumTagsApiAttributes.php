<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Listener;

use Flarum\Api\Event\Serializing;
use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Settings\SettingsRepositoryInterface;

class PrepareForumTagsApiAttributes
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    public function handle(Serializing $event)
    {
        if ($event->isSerializer(ForumSerializer::class)) {
            $event->attributes['minPrimaryTags'] = $this->settings->get('flarum-tags.min_primary_tags');
            $event->attributes['maxPrimaryTags'] = $this->settings->get('flarum-tags.max_primary_tags');
            $event->attributes['minSecondaryTags'] = $this->settings->get('flarum-tags.min_secondary_tags');
            $event->attributes['maxSecondaryTags'] = $this->settings->get('flarum-tags.max_secondary_tags');
        }
    }
}
