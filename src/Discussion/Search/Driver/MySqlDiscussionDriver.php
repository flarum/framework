<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Discussion\Search\Driver;

use Flarum\Post\Post;
use Flarum\Discussion\Discussion;

class MySqlDiscussionDriver implements DriverInterface
{
    /**
     * {@inheritdoc}
     */
    public function match($string)
    {
        $contentDiscussionIds = Post::where('type', 'comment')
            ->whereRaw('MATCH (`content`) AGAINST (? IN BOOLEAN MODE)', [$string])
            ->orderByRaw('MATCH (`content`) AGAINST (?) DESC', [$string])
            ->lists('discussion_id', 'id');
        $titleDiscussionIds = Discussion::where('is_approved', 1)
            ->whereRaw('MATCH (`title`) AGAINST (? IN BOOLEAN MODE)', [$string])
            ->orderByRaw('MATCH (`title`) AGAINST (?) DESC', [$string])
            ->lists('id');

        $relevantPostIds = [];

        foreach ($contentDiscussionIds as $postId => $discussionId) {
            $relevantPostIds[$discussionId][] = $postId;
        }

        foreach ($titleDiscussionIds as $postId => $discussionId) {
            $relevantPostIds[$discussionId][] = $postId;
        }

        return $relevantPostIds;
    }
}
