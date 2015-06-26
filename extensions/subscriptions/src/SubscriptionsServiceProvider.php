<?php namespace Flarum\Subscriptions;

use Flarum\Support\ServiceProvider;
use Flarum\Extend;

class SubscriptionsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../views', 'flarum-subscriptions');

        $this->extend([
            (new Extend\Locale('en'))->translations(__DIR__.'/../locale/en.yml'),

            (new Extend\ForumClient())
                ->assets([
                    __DIR__.'/../js/dist/extension.js',
                    __DIR__.'/../less/extension.less'
                ])
                ->translations([
                    // Add the keys of translations you would like to be available
                    // for use by the JS client application.
                ])
                ->route('get', '/following', 'flarum.forum.following'),

            (new Extend\ApiSerializer('Flarum\Api\Serializers\DiscussionSerializer'))
                ->attributes(function (&$attributes, $discussion, $user) {
                    if ($state = $discussion->stateFor($user)) {
                        $attributes['subscription'] = $state->subscription ?: false;
                    }
                }),

            new Extend\EventSubscriber('Flarum\Subscriptions\Handlers\SubscriptionSaver'),
            new Extend\EventSubscriber('Flarum\Subscriptions\Handlers\SubscriptionSearchModifier'),
            new Extend\EventSubscriber('Flarum\Subscriptions\Handlers\NewPostNotifier'),

            new Extend\DiscussionGambit('Flarum\Subscriptions\SubscriptionGambit'),

            (new Extend\NotificationType('Flarum\Subscriptions\NewPostNotification', 'Flarum\Api\Serializers\DiscussionBasicSerializer'))
                ->enableByDefault('alert')
                ->enableByDefault('email')
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
