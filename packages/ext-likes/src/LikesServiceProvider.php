<?php namespace Flarum\Likes;

use Flarum\Support\ServiceProvider;
use Flarum\Extend;

class LikesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->extend([
            (new Extend\Locale('en'))->translations(__DIR__.'/../locale/en.yml'),

            (new Extend\ForumClient)
                ->assets([
                    __DIR__.'/../js/dist/extension.js',
                    __DIR__.'/../less/extension.less'
                ]),

            (new Extend\Model('Flarum\Core\Models\Post'))
                ->belongsToMany('likes', 'Flarum\Core\Models\User', 'posts_likes', 'post_id', 'user_id'),

            (new Extend\ApiSerializer('Flarum\Api\Serializers\PostSerializer'))
                ->hasMany('likes', 'Flarum\Api\Serializers\UserBasicSerializer')
                ->attributes(function (&$attributes, $post, $user) {
                    $attributes['canLike'] = $post->can($user, 'like');
                }),

            (new Extend\ApiAction('Flarum\Api\Actions\Discussions\ShowAction'))
                ->addInclude('posts.likes'),

            (new Extend\ApiAction([
                'Flarum\Api\Actions\Posts\IndexAction',
                'Flarum\Api\Actions\Posts\ShowAction',
                'Flarum\Api\Actions\Posts\CreateAction',
                'Flarum\Api\Actions\Posts\UpdateAction'
            ]))
                ->addInclude('likes'),

            new Extend\EventSubscriber('Flarum\Likes\Handlers\LikedSaver'),
            new Extend\EventSubscriber('Flarum\Likes\Handlers\PostLikedNotifier'),

            (new Extend\NotificationType(
                'Flarum\Likes\PostLikedNotification',
                'Flarum\Api\Serializers\PostBasicSerializer'
            ))
                ->enableByDefault('alert')
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
