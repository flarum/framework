<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Reports\Listeners;

use Flarum\Events\ApiRelationship;
use Flarum\Events\WillSerializeData;
use Flarum\Events\BuildApiAction;
use Flarum\Events\ApiAttributes;
use Flarum\Events\RegisterApiRoutes;
use Flarum\Api\Serializers\PostSerializer;
use Flarum\Api\Serializers\ForumSerializer;
use Flarum\Api\Actions\Posts;
use Flarum\Api\Actions\Discussions;
use Flarum\Reports\Report;
use Flarum\Reports\Api\CreateAction as ReportsCreateAction;
use Illuminate\Database\Eloquent\Collection;

class AddApiAttributes
{
    public function subscribe($events)
    {
        $events->listen(ApiRelationship::class, [$this, 'addReportsRelationship']);
        $events->listen(WillSerializeData::class, [$this, 'loadReportsRelationship']);
        $events->listen(BuildApiAction::class, [$this, 'includeReportsRelationship']);
        $events->listen(ApiAttributes::class, [$this, 'addAttributes']);
        $events->listen(RegisterApiRoutes::class, [$this, 'addRoutes']);
    }

    public function loadReportsRelationship(WillSerializeData $event)
    {
        // For any API action that allows the 'reports' relationship to be
        // included, we need to preload this relationship onto the data (Post
        // models) so that we can selectively expose only the reports that the
        // user has permission to view.
        if ($event->action instanceof Discussions\ShowAction) {
            $discussion = $event->data;
            $posts = $discussion->posts->all();
        }

        if ($event->action instanceof Posts\IndexAction) {
            $posts = $event->data->all();
        }

        if ($event->action instanceof Posts\ShowAction) {
            $posts = [$event->data];
        }

        if ($event->action instanceof ReportsCreateAction) {
            $report = $event->data;
            $posts = [$report->post];
        }

        if (isset($posts)) {
            $actor = $event->request->actor;
            $postsWithPermission = [];

            foreach ($posts as $post) {
                $post->setRelation('reports', null);

                if ($post->discussion->can($actor, 'viewReports')) {
                    $postsWithPermission[] = $post;
                }
            }

            if (count($postsWithPermission)) {
                (new Collection($postsWithPermission))
                    ->load('reports', 'reports.user');
            }
        }
    }

    public function addReportsRelationship(ApiRelationship $event)
    {
        if ($event->serializer instanceof PostSerializer &&
            $event->relationship === 'reports') {
            return $event->serializer->hasMany('Flarum\Reports\Api\ReportSerializer', 'reports');
        }
    }

    public function includeReportsRelationship(BuildApiAction $event)
    {
        if ($event->action instanceof Discussions\ShowAction) {
            $event->addInclude('posts.reports');
            $event->addInclude('posts.reports.user');
        }

        if ($event->action instanceof Posts\IndexAction ||
            $event->action instanceof Posts\ShowAction) {
            $event->addInclude('reports');
            $event->addInclude('reports.user');
        }
    }

    public function addAttributes(ApiAttributes $event)
    {
        if ($event->serializer instanceof ForumSerializer) {
            $event->attributes['canViewReports'] = $event->actor->hasPermissionLike('discussion.viewReports');

            if ($event->attributes['canViewReports']) {
                $query = Report::whereVisibleTo($event->actor);

                if ($time = $event->actor->reports_read_time) {
                    $query->where('reports.time', '>', $time);
                }

                $event->attributes['unreadReportsCount'] = $query->distinct('reports.post_id')->count();
            }
        }

        if ($event->serializer instanceof PostSerializer) {
            $event->attributes['canReport'] = $event->model->can($event->actor, 'report');
        }
    }

    public function addRoutes(RegisterApiRoutes $event)
    {
        $event->get('/reports', 'reports.index', 'Flarum\Reports\Api\IndexAction');
        $event->post('/reports', 'reports.create', 'Flarum\Reports\Api\CreateAction');
        $event->delete('/posts/{id}/reports', 'reports.delete', 'Flarum\Reports\Api\DeleteAction');
    }
}
