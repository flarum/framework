<?php namespace Flarum\Mentions;

use Flarum\Support\ServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Flarum\Api\Actions\Discussions\ShowAction as DiscussionsShowAction;
use Flarum\Api\Actions\Posts\IndexAction as PostsIndexAction;
use Flarum\Api\Actions\Posts\ShowAction as PostsShowAction;
use Flarum\Api\Actions\Posts\CreateAction as PostsCreateAction;

class MentionsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot(Dispatcher $events)
    {
        $events->subscribe('Flarum\Mentions\Handlers\PostMentionsMetadataUpdater');
        $events->subscribe('Flarum\Mentions\Handlers\UserMentionsMetadataUpdater');

        $this->forumAssets([
            __DIR__.'/../js/dist/extension.js',
            __DIR__.'/../less/mentions.less'
        ]);

        $this->relationship('Flarum\Core\Models\Post', function ($model) {
            return $model->belongsToMany('Flarum\Core\Models\Post', 'mentions_posts', 'mentions_id');
        }, 'mentionedBy');

        $this->serializeRelationship('Flarum\Api\Serializers\PostSerializer', 'hasMany', 'mentionedBy', 'Flarum\Api\Serializers\PostBasicSerializer');

        DiscussionsShowAction::$include['posts.mentionedBy'] = true;
        DiscussionsShowAction::$include['posts.mentionedBy.user'] = true;

        PostsShowAction::$include['mentionedBy'] = true;
        PostsShowAction::$include['mentionedBy.user'] = true;

        PostsIndexAction::$include['mentionedBy'] = true;
        PostsIndexAction::$include['mentionedBy.user'] = true;


        $this->relationship('Flarum\Core\Models\Post', function ($model) {
            return $model->belongsToMany('Flarum\Core\Models\Post', 'mentions_posts', 'post_id', 'mentions_id');
        }, 'mentionsPosts');

        $this->relationship('Flarum\Core\Models\Post', function ($model) {
            return $model->belongsToMany('Flarum\Core\Models\User', 'mentions_users', 'post_id', 'mentions_id');
        }, 'mentionsUsers');

        $this->serializeRelationship('Flarum\Api\Serializers\PostSerializer', 'hasMany', 'mentionsPosts', 'Flarum\Api\Serializers\PostBasicSerializer');
        $this->serializeRelationship('Flarum\Api\Serializers\PostSerializer', 'hasMany', 'mentionsUsers', 'Flarum\Api\Serializers\UserBasicSerializer');

        DiscussionsShowAction::$include['posts.mentionsPosts'] = true;
        DiscussionsShowAction::$include['posts.mentionsPosts.user'] = true;

        PostsCreateAction::$include['mentionsPosts'] = true;
        PostsCreateAction::$include['mentionsPosts.mentionedBy'] = true;

        DiscussionsShowAction::$include['posts.mentionsUsers'] = true;


        $this->formatter('postMentions', 'Flarum\Mentions\PostMentionsFormatter');
        $this->formatter('userMentions', 'Flarum\Mentions\UserMentionsFormatter');

        $this->notificationType('Flarum\Mentions\PostMentionedNotification', ['alert' => true]);
        $this->notificationType('Flarum\Mentions\UserMentionedNotification', ['alert' => true]);
    }
}
