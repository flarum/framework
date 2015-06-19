<?php namespace Flarum\Mentions;

use Flarum\Support\ServiceProvider;
use Flarum\Extend;

class MentionsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../views', 'mentions');

        $this->extend(
            new Extend\EventSubscriber([
                'Flarum\Mentions\Handlers\PostMentionsMetadataUpdater',
                'Flarum\Mentions\Handlers\UserMentionsMetadataUpdater'
            ]),

            (new Extend\ForumClient())
                ->assets([
                    __DIR__.'/../js/dist/extension.js',
                    __DIR__.'/../less/mentions.less'
                ]),

            (new Extend\Model('Flarum\Core\Models\Post'))
                ->belongsToMany('mentionedBy', 'Flarum\Core\Models\Post', 'mentions_posts', 'mentions_id')
                ->belongsToMany('mentionsPosts', 'Flarum\Core\Models\Post', 'mentions_posts', 'post_id', 'mentions_id')
                ->belongsToMany('mentionsUsers', 'Flarum\Core\Models\User', 'mentions_users', 'post_id', 'mentions_id'),

            (new Extend\ApiSerializer('Flarum\Api\Serializers\PostSerializer'))
                ->hasMany('mentionedBy', 'Flarum\Api\Serializers\PostBasicSerializer')
                ->hasMany('mentionsPosts', 'Flarum\Api\Serializers\PostBasicSerializer')
                ->hasMany('mentionsUsers', 'Flarum\Api\Serializers\UserBasicSerializer'),

            (new Extend\ApiAction('Flarum\Api\Actions\Discussions\ShowAction'))
                ->addInclude('posts.mentionedBy')
                ->addInclude('posts.mentionedBy.user')
                ->addLink('posts.mentionedBy.discussion')
                ->addInclude('posts.mentionsPosts', false)
                ->addInclude('posts.mentionsPosts.user', false)
                ->addInclude('posts.mentionsUsers', false),

            (new Extend\ApiAction([
                'Flarum\Api\Actions\Posts\IndexAction',
                'Flarum\Api\Actions\Posts\ShowAction',
            ]))
                ->addInclude('mentionedBy')
                ->addInclude('mentionedBy.user')
                ->addLink('mentionedBy.discussion'),

            (new Extend\ApiAction('Flarum\Api\Actions\Posts\CreateAction'))
                ->addInclude('mentionsPosts')
                ->addInclude('mentionsPosts.mentionedBy'),

            new Extend\Formatter('postMentions', 'Flarum\Mentions\PostMentionsFormatter'),

            new Extend\Formatter('userMentions', 'Flarum\Mentions\UserMentionsFormatter'),

            new Extend\ActivityType('Flarum\Mentions\PostMentionedActivity', 'Flarum\Api\Serializers\PostBasicSerializer'),

            new Extend\ActivityType('Flarum\Mentions\UserMentionedActivity', 'Flarum\Api\Serializers\PostBasicSerializer'),

            (new Extend\NotificationType('Flarum\Mentions\PostMentionedNotification', 'Flarum\Api\Serializers\PostBasicSerializer'))
                ->enableByDefault('alert'),

            (new Extend\NotificationType('Flarum\Mentions\UserMentionedNotification', 'Flarum\Api\Serializers\PostBasicSerializer'))
                ->enableByDefault('alert')
        );
    }
}
