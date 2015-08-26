<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Application;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Flarum\Core\Users\User;
use Flarum\Core\Discussions\Discussion;
use Flarum\Core\Discussions\DiscussionState;
use Flarum\Core\Posts\CommentPost;
use Flarum\Tags\Tag;
use Flarum\Events\PostWasPosted;
use Symfony\Component\Console\Helper\ProgressBar;

class ImportCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import from esoTalk.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Application $app)
    {
        parent::__construct();

        $this->app = $app;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        if (!$this->confirm('Warning: all Flarum tables will be truncated. Proceed? [y|N]', false)) {
            return;
        }

        app('config')->set('database.connections.esotalk', [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST'),
            'database'  => 'esotalk',
            'username'  => env('DB_USERNAME'),
            'password'  => env('DB_PASSWORD'),
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => 'et_',
            'strict'    => false,
        ]);

        User::$rules = [];

        $from = app('db')->connection('esotalk');
        $to = app('db')->connection();

        $this->importTags($from, $to);
        $this->importUsers($from, $to);
        $this->importDiscussions($from, $to);

        $to->table('notifications')->update(['is_read' => true]);
    }

    protected function importTags($from, $to)
    {
        $colors = ['#F16655', '#F59B66', '#4E89DA', '#5AC169', '#96A2AF'];

        $this->info('Importing tags...');

        $to->table('tags')->truncate();

        $channels = $from->table('channel')->orderBy('lft')->get();

        $progress = new ProgressBar($this->output, count($channels));

        $i = 0;
        foreach ($channels as $c) {
            $tag = new Tag;

            $tag->id                = $c->channelId;
            $tag->name              = $c->title;
            $tag->slug              = $c->slug;
            $tag->description       = $c->description;
            $tag->color             = $colors[$i++ % count($colors)];
            $tag->discussions_count = $c->countConversations;
            $tag->position          = $c->lft;

            $tag->save();

            $progress->advance();
        }

        $progress->finish();
        $this->info("\n");
    }

    protected function importUsers($from, $to)
    {
        $this->info('Importing users...');

        $to->table('users')->truncate();
        $to->table('email_tokens')->truncate();
        $to->table('password_tokens')->truncate();
        $to->table('access_tokens')->truncate();
        $to->table('users_groups')->truncate();

        $members = $from->table('member')->get();

        $progress = new ProgressBar($this->output, count($members));

        foreach ($members as $m) {
            $preferences = unserialize($m->preferences);
            $user = new User;

            $user->id                     = $m->memberId;
            $user->username               = $m->username;
            $user->email                  = $m->email;
            $user->is_activated           = true;
            $user->password               = '';
            $user->join_time              = $m->joinTime;
            $user->last_seen_time         = $m->lastActionTime;
            $user->avatar_path            = $m->avatarFormat ? $m->memberId.'.'.$m->avatarFormat : null;
            $user->username               = $m->username;
            $user->read_time              = array_get($preferences, 'markedAllConversationsAsRead');
            $user->notification_read_time = array_get($preferences, 'notificationCheckTime');
            $user->preferences            = ['discloseOnline' => !array_get($preferences, 'hideOnline')];
            $user->discussions_count      = $m->countConversations;
            $user->comments_count         = $m->countPosts;

            $user->save();

            $this->app->make('Flarum\Core\Activity\ActivitySyncer')
                ->sync(new JoinedActivity($user), [$user]);

            $progress->advance();
        }

        $progress->finish();
        $this->info("\n");
    }

    protected function importDiscussions($from, $to)
    {
        $this->info('Importing discussions...');

        $to->table('discussions')->truncate();
        $to->table('discussions_tags')->truncate();
        $to->table('posts')->truncate();
        $to->table('notifications')->truncate();
        $to->table('users_discussions')->truncate();
        $to->table('activity')->truncate();
        $to->table('mentions_posts')->truncate();
        $to->table('mentions_users')->truncate();

        $conversations = $from->table('conversation')->where('private', 0)->get();

        $progress = new ProgressBar($this->output, count($conversations));

        foreach ($conversations as $c) {
            $discussion = new Discussion;

            $discussion->id             = $c->conversationId;
            $discussion->title          = $c->title;
            $discussion->is_sticky      = $c->sticky;

            $discussion->start_user_id  = $c->startMemberId;
            $discussion->start_time     = $c->startTime;

            $discussion->last_user_id   = $c->lastPostMemberId;
            $discussion->last_time      = $c->lastPostTime;

            $discussion->save();

            $discussion->tags()->sync([$c->channelId]);

            foreach ($from->table('post')->where('conversationId', $c->conversationId)->get() as $p) {
                $post = new CommentPost;

                $post->id            = $p->postId;
                $post->discussion_id = $p->conversationId;
                $post->user_id       = $p->memberId;
                $post->time          = $p->time;
                $post->edit_user_id  = $p->editMemberId;
                $post->edit_time     = $p->editTime;
                $post->hide_user_id  = $p->deleteMemberId;
                $post->hide_time     = $p->deleteTime;
                $post->content       = $p->content;

                $this->formatPost($post);

                $post->save();

                if (!$post->hide_time) {
                    event(new PostWasPosted($post));
                }
            }

            $discussion->last_post_id = $p->postId;
            $discussion->last_post_number = $post->number;
            $discussion->comments_count = $post->number;

            $discussion->save();

            $states = $from->table('member_conversation')
                ->where('conversationId', $c->conversationId)
                ->where('type', 'member')
                ->get();
            foreach ($states as $s) {
                $state = new DiscussionState;

                $state->discussion_id = $s->conversationId;
                $state->user_id = $s->id;
                $state->read_time = time();
                $state->read_number = $discussion->posts()->orderBy('time', 'asc')->skip(min($discussion->comments_count, $s->lastRead) - 1)->pluck('number');

                $state->save();
            }

            $progress->advance();
        }

        $progress->finish();
        $this->info("\n");
    }

    protected function formatPost($post)
    {
        // Code blocks
        $regexp = "/(.*)^\s*\[code\]\n?(.*?)\n?\[\/code]$/ims";
        while (preg_match($regexp, $post->content)) {
            $post->content = preg_replace($regexp, "$1```\n$2\n```", $post->content);
        }

        // Inline tags
        $replace = [
            '/\[url=(.*?)\](.*?)\[\/url\]/i' => '[$2]($1)',
            '/\[b\](.*?)\[\/b\]/i' => '**$1**',
            '/\[i\](.*?)\[\/i\]/i' => '*$1*',
            '/\[h\](.*?)\[\/h\]/i' => '# $1',
            '/\[img\](.*?)\[\/img\]/i' => '![]($1)',
            '/\[code\](.*?)\[\/code\]/i' => '`$1`'
        ];
        $post->content = preg_replace(array_keys($replace), array_values($replace), $post->content);

        // Quotes
        $regexp = "/(.*?)\n?\[quote(?:=(.*?)(]?))?\]\n?(.*?)\n?\[\/quote\]\n{0,2}/is";
        while (preg_match($regexp, $post->content)) {
            $post->content = preg_replace_callback($regexp, function ($matches) use ($post) {
                if (strpos($matches[2], ':') !== false) {
                    list($postId, $user) = explode(':', $matches[2]);
                    $mentionedPost = CommentPost::find($postId);

                    return $matches[1]."\n@".$mentionedPost->user->username.'#'.$mentionedPost->number.' ';
                } else {
                    return $matches[1].'> '.str_replace("\n", "\n> ", $matches[4])."\n\n";
                }
            }, $post->content);
        }
    }
}
