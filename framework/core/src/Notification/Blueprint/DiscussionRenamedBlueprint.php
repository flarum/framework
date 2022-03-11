<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification\Blueprint;

use Flarum\Discussion\Discussion;
use Flarum\Post\DiscussionRenamedPost;

class DiscussionRenamedBlueprint implements BlueprintInterface
{
    /**
     * @var \Flarum\Post\DiscussionRenamedPost
     */
    protected $post;

    /**
     * @param DiscussionRenamedPost $post
     */
    public function __construct(DiscussionRenamedPost $post)
    {
        $this->post = $post;
    }

    /**
     * {@inheritdoc}
     */
    public function getFromUser()
    {
        return $this->post->user;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        return $this->post->discussion;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return ['postNumber' => (int) $this->post->number];
    }

    /**
     * {@inheritdoc}
     */
    public static function getType()
    {
        return 'discussionRenamed';
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubjectModel()
    {
        return Discussion::class;
    }
}
