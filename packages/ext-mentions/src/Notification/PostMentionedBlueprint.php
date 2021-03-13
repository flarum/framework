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

class PostMentionedBlueprint implements BlueprintInterface, MailableInterface
{
    /**
     * @var Post
     */
    public $post;

    /**
     * @var Post
     */
    public $reply;

    /**
     * @param Post $post
     * @param Post $reply
     */
    public function __construct(Post $post, Post $reply)
    {
        $this->post = $post;
        $this->reply = $reply;
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
        return $this->reply->user;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return ['replyNumber' => (int) $this->reply->number];
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailView()
    {
        return ['text' => 'flarum-mentions::emails.postMentioned'];
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailSubject(TranslatorInterface $translator)
    {
        return $translator->trans('flarum-mentions.email.post_mentioned.subject', [
            '{replier_display_name}' => $this->reply->user->display_name,
            '{title}' => $this->post->discussion->title
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getType()
    {
        return 'postMentioned';
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubjectModel()
    {
        return Post::class;
    }
}
