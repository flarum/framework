<?php namespace Flarum\Sticky;

use Flarum\Support\ServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;

class StickyServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot(Dispatcher $events)
    {
        $events->subscribe('Flarum\Sticky\Handlers\StickySaver');
        $events->subscribe('Flarum\Sticky\Handlers\StickySearchModifier');
        $events->subscribe('Flarum\Sticky\Handlers\DiscussionStickiedNotifier');

        $this->forumAssets([
            __DIR__.'/../js/dist/extension.js',
            __DIR__.'/../less/sticky.less'
        ]);

        $this->postType('Flarum\Sticky\DiscussionStickiedPost');

        $this->serializeAttributes('Flarum\Api\Serializers\DiscussionSerializer', function (&$attributes, $model, $serializer) {
            $attributes['isSticky'] = (bool) $model->is_sticky;
            $attributes['canSticky'] = (bool) $model->can($serializer->actor->getUser(), 'sticky');
        });

        $this->discussionGambit('Flarum\Sticky\StickyGambit');

        $this->notificationType('Flarum\Sticky\DiscussionStickiedNotification', ['alert' => true]);

        $this->permission('discussion.sticky');
    }
}
