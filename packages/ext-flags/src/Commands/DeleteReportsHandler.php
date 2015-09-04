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
use Flarum\Reports\Events\ReportsWillBeDeleted;

class DeleteReportsHandler
{
    protected $posts;

    public function __construct(PostRepository $posts)
    {
        $this->posts = $posts;
    }

    /**
     * @param DeleteReport $command
     * @return Report
     * @throws \Flarum\Core\Exceptions\PermissionDeniedException
     */
    public function handle(DeleteReports $command)
    {
        $actor = $command->actor;

        $post = $this->posts->findOrFail($command->postId, $actor);

        $post->discussion->assertCan($actor, 'viewReports');

        event(new ReportsWillBeDeleted($post, $actor, $command->data));

        $post->reports()->delete();

        return $post;
    }
}
