<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Reports\Commands;

use Flarum\Reports\Report;
use Flarum\Core\Posts\PostRepository;
use Flarum\Core\Posts\CommentPost;
use Exception;

class CreateReportHandler
{
    private $posts;

    public function __construct(PostRepository $posts)
    {
        $this->posts = $posts;
    }

    /**
     * @param CreateReport $command
     * @return Report
     */
    public function handle(CreateReport $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        $postId = array_get($data, 'relationships.post.data.id');
        $post = $this->posts->findOrFail($postId, $actor);

        if (! ($post instanceof CommentPost)) {
            // TODO: throw 400(?) error
            throw new Exception;
        }

        $post->assertCan($actor, 'report');

        Report::unguard();

        $report = Report::firstOrNew([
            'post_id' => $post->id,
            'user_id' => $actor->id
        ]);

        $report->post_id = $post->id;
        $report->user_id = $actor->id;
        $report->reason = array_get($data, 'attributes.reason');
        $report->reason_detail = array_get($data, 'attributes.reasonDetail');
        $report->time = time();

        $report->save();

        return $report;
    }
}
