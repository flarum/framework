<?php namespace Flarum\Core\Discussions\Search\Fulltext;

use Flarum\Core\Posts\Post;

class MySqlFulltextDriver implements Driver
{
    /**
     * {@inheritdoc}
     */
    public function match($string)
    {
        $discussionIds = Post::where('type', 'comment')
            ->whereRaw('MATCH (`content`) AGAINST (? IN BOOLEAN MODE)', [$string])
            ->orderByRaw('MATCH (`content`) AGAINST (?) DESC', [$string])
            ->lists('discussion_id', 'id');

        $relevantPostIds = [];

        foreach ($discussionIds as $postId => $discussionId) {
            $relevantPostIds[$discussionId][] = $postId;
        }

        return $relevantPostIds;
    }
}
