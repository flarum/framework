<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Flags\Commands;

use Flarum\Flags\Flag;
use Flarum\Core\Posts\PostRepository;
use Flarum\Flags\Events\FlagsWillBeDeleted;

class DeleteFlagsHandler
{
    protected $posts;

    public function __construct(PostRepository $posts)
    {
        $this->posts = $posts;
    }

    /**
     * @param DeleteFlag $command
     * @return Flag
     * @throws \Flarum\Core\Exceptions\PermissionDeniedException
     */
    public function handle(DeleteFlags $command)
    {
        $actor = $command->actor;

        $post = $this->posts->findOrFail($command->postId, $actor);

        $post->discussion->assertCan($actor, 'viewFlags');

        event(new FlagsWillBeDeleted($post, $actor, $command->data));

        $post->flags()->delete();

        return $post;
    }
}
