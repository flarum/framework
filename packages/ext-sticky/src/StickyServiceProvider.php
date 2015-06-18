<?php namespace Flarum\Sticky;

use Flarum\Support\ServiceProvider;
use Flarum\Extend;

class StickyServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->extend(
            new Extend\EventSubscriber([
                'Flarum\Sticky\Handlers\StickySaver',
                'Flarum\Sticky\Handlers\StickySearchModifier',
                'Flarum\Sticky\Handlers\DiscussionStickiedNotifier'
            ]),

            (new Extend\ForumClient())
                ->assets([
                    __DIR__.'/../js/dist/extension.js',
                    __DIR__.'/../less/sticky.less'
                ]),

            new Extend\PostType('Flarum\Sticky\DiscussionStickiedPost'),

            (new Extend\ApiSerializer('Flarum\Api\Serializers\DiscussionSerializer'))
                ->attributes(function (&$attributes, $model, $user) {
                    $attributes['isSticky'] = (bool) $model->is_sticky;
                    $attributes['canSticky'] = (bool) $model->can($user, 'sticky');
                }),

            new Extend\DiscussionGambit('Flarum\Sticky\StickyGambit'),

            (new Extend\NotificationType('Flarum\Sticky\DiscussionStickiedNotification', 'Flarum\Api\Serializers\DiscussionBasicSerializer'))
                ->enableByDefault('alert')
        );
    }
}
