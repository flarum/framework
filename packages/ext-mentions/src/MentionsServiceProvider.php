<?php namespace Flarum\Mentions;

use Flarum\Support\ServiceProvider;
use Flarum\Extend\EventSubscribers;
use Flarum\Extend\ForumAssets;
use Flarum\Extend\Relationship;
use Flarum\Extend\SerializeRelationship;
use Flarum\Extend\ApiInclude;
use Flarum\Extend\Formatter;
use Flarum\Extend\NotificationType;
use Flarum\Extend\ActivityType;

class MentionsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../views', 'mentions');

        $this->extend(
            new EventSubscribers([
                'Flarum\Mentions\Handlers\PostMentionsMetadataUpdater',
                'Flarum\Mentions\Handlers\UserMentionsMetadataUpdater'
            ]),

            new ForumAssets([
                __DIR__.'/../js/dist/extension.js',
                __DIR__.'/../less/mentions.less'
            ]),

            new Relationship('Flarum\Core\Models\Post', 'mentionedBy', function ($model) {
                return $model->belongsToMany('Flarum\Core\Models\Post', 'mentions_posts', 'mentions_id');
            }),

            new Relationship('Flarum\Core\Models\Post', 'mentionsPosts', function ($model) {
                return $model->belongsToMany('Flarum\Core\Models\Post', 'mentions_posts', 'post_id', 'mentions_id');
            }),

            new Relationship('Flarum\Core\Models\Post', 'mentionsUsers', function ($model) {
                return $model->belongsToMany('Flarum\Core\Models\User', 'mentions_users', 'post_id', 'mentions_id');
            }),

            new SerializeRelationship('Flarum\Api\Serializers\PostSerializer', 'hasMany', 'mentionedBy', 'Flarum\Api\Serializers\PostBasicSerializer'),

            new SerializeRelationship('Flarum\Api\Serializers\PostSerializer', 'hasMany', 'mentionsPosts', 'Flarum\Api\Serializers\PostBasicSerializer'),

            new SerializeRelationship('Flarum\Api\Serializers\PostSerializer', 'hasMany', 'mentionsUsers', 'Flarum\Api\Serializers\UserBasicSerializer'),

            new ApiInclude('discussions.show', ['posts.mentionedBy', 'posts.mentionedBy.user', 'posts.mentionsPosts', 'posts.mentionsPosts.user', 'posts.mentionsUsers'], true),

            new ApiInclude(['posts.index', 'posts.show'], ['mentionedBy', 'mentionedBy.user'], true),

            new ApiInclude(['posts.create'], ['mentionsPosts', 'mentionsPosts.mentionedBy'], true),

            new Formatter('postMentions', 'Flarum\Mentions\PostMentionsFormatter'),

            new Formatter('userMentions', 'Flarum\Mentions\UserMentionsFormatter'),

            new ActivityType('Flarum\Mentions\PostMentionedActivity', 'Flarum\Api\Serializers\PostBasicSerializer'),

            new ActivityType('Flarum\Mentions\UserMentionedActivity', 'Flarum\Api\Serializers\PostBasicSerializer'),

            (new NotificationType('Flarum\Mentions\PostMentionedNotification', 'Flarum\Api\Serializers\PostBasicSerializer'))
                ->enableByDefault('alert'),

            (new NotificationType('Flarum\Mentions\UserMentionedNotification', 'Flarum\Api\Serializers\PostBasicSerializer'))
                ->enableByDefault('alert')
        );
    }
}
