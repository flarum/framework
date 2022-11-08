<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Notification;

use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\MailableInterface;
use Flarum\Post\Post;
use Symfony\Contracts\Translation\TranslatorInterface;

class GroupMentionedBlueprint implements BlueprintInterface, MailableInterface
{
    /**
     * @var Post
     */
    public $post;

    /**
     * @param Post $post
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        return $this->post;
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
    public function getData()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailView()
    {
        return ['text' => 'flarum-mentions::emails.groupMentioned'];
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailSubject(TranslatorInterface $translator)
    {
        return $translator->trans('flarum-mentions.email.group_mentioned.subject', [
            '{mentioner_display_name}' => $this->post->user->display_name,
            '{title}' => $this->post->discussion->title
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getType()
    {
        return 'groupMentioned';
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubjectModel()
    {
        return Post::class;
    }
}
