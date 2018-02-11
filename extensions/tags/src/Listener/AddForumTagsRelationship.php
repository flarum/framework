<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Listener;

use Flarum\Api\Controller\ShowForumController;
use Flarum\Api\Event\Serializing;
use Flarum\Api\Event\WillGetData;
use Flarum\Api\Event\WillSerializeData;
use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Event\GetApiRelationship;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tags\Tag;
use Illuminate\Contracts\Events\Dispatcher;

class AddForumTagsRelationship
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

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(GetApiRelationship::class, [$this, 'getApiRelationship']);
        $events->listen(WillSerializeData::class, [$this, 'loadTagsRelationship']);
        $events->listen(WillGetData::class, [$this, 'includeTagsRelationship']);
        $events->listen(Serializing::class, [$this, 'prepareApiAttributes']);
    }

    /**
     * @param GetApiRelationship $event
     * @return \Tobscure\JsonApi\Relationship|null
     */
    public function getApiRelationship(GetApiRelationship $event)
    {
        if ($event->isRelationship(ForumSerializer::class, 'tags')) {
            return $event->serializer->hasMany($event->model, 'Flarum\Tags\Api\Serializer\TagSerializer', 'tags');
        }
    }

    /**
     * @param WillSerializeData $event
     */
    public function loadTagsRelationship(WillSerializeData $event)
    {
        // Expose the complete tag list to clients by adding it as a
        // relationship to the /api endpoint. Since the Forum model
        // doesn't actually have a tags relationship, we will manually load and
        // assign the tags data to it using an event listener.
        if ($event->isController(ShowForumController::class)) {
            $event->data['tags'] = Tag::whereVisibleTo($event->actor)
                ->withStateFor($event->actor)
                ->with(['parent', 'lastDiscussion'])
                ->get();
        }
    }

    /**
     * @param WillGetData $event
     */
    public function includeTagsRelationship(WillGetData $event)
    {
        if ($event->isController(ShowForumController::class)) {
            $event->addInclude(['tags', 'tags.lastDiscussion', 'tags.parent']);
        }
    }

    /**
     * @param Serializing $event
     */
    public function prepareApiAttributes(Serializing $event)
    {
        if ($event->isSerializer(ForumSerializer::class)) {
            $event->attributes['minPrimaryTags'] = $this->settings->get('flarum-tags.min_primary_tags');
            $event->attributes['maxPrimaryTags'] = $this->settings->get('flarum-tags.max_primary_tags');
            $event->attributes['minSecondaryTags'] = $this->settings->get('flarum-tags.min_secondary_tags');
            $event->attributes['maxSecondaryTags'] = $this->settings->get('flarum-tags.max_secondary_tags');
        }
    }
}
