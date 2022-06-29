<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes\Api;

use Flarum\Api\Serializer\BasicUserSerializer;
use Flarum\Api\Serializer\PostSerializer;
use Flarum\Post\Post;
use Tobscure\JsonApi\Collection;
use Tobscure\JsonApi\Relationship;

class RecentLikesRelationship
{
    private BasicUserSerializer $serializer;

    public function __construct(BasicUserSerializer $serializer)
    {
        $this->serializer = $serializer;
    }

    public function __invoke(PostSerializer $serializer, Post $post): Relationship
    {
        return new Relationship(new Collection(
            $post->recentLikes,
            $this->serializer
        ));
    }
}
