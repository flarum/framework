<?php namespace Flarum\Sticky;

use Flarum\Support\ServiceProvider;
use Flarum\Extend\EventSubscribers;
use Flarum\Extend\ForumAssets;
use Flarum\Extend\PostType;
use Flarum\Extend\SerializeAttributes;
use Flarum\Extend\DiscussionGambit;
use Flarum\Extend\NotificationType;
use Flarum\Extend\Permission;

class StickyServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->extend(
            new EventSubscribers([
                'Flarum\Sticky\Handlers\StickySaver',
                'Flarum\Sticky\Handlers\StickySearchModifier',
                'Flarum\Sticky\Handlers\DiscussionStickiedNotifier'
            ]),

            new ForumAssets([
                __DIR__.'/../js/dist/extension.js',
                __DIR__.'/../less/sticky.less'
            ]),

            new PostType('Flarum\Sticky\DiscussionStickiedPost'),

            new SerializeAttributes('Flarum\Api\Serializers\DiscussionSerializer', function (&$attributes, $model, $serializer) {
                $attributes['isSticky'] = (bool) $model->is_sticky;
                $attributes['canSticky'] = (bool) $model->can($serializer->actor->getUser(), 'sticky');
            }),

            new DiscussionGambit('Flarum\Sticky\StickyGambit'),

            (new NotificationType('Flarum\Sticky\DiscussionStickiedNotification'))->enableByDefault('alert'),

            new Permission('discussion.sticky')
        );
    }
}
