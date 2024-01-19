<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Notification;

use Flarum\Database\AbstractModel;
use Flarum\Locale\TranslatorInterface;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\MailableInterface;
use Flarum\Post\Post;
use Flarum\User\User;

class PostMentionedBlueprint implements BlueprintInterface, MailableInterface
{
    public function __construct(
        public Post $post,
        public Post $reply
    ) {
    }

    public function getSubject(): ?AbstractModel
    {
        return $this->post;
    }

    public function getFromUser(): ?User
    {
        return $this->reply->user;
    }

    public function getData(): array
    {
        return ['replyNumber' => (int) $this->reply->number];
    }

    public function getEmailViews(): array
    {
        return [
            'text' => 'flarum-mentions::emails.plain.postMentioned',
            'html' => 'flarum-mentions::emails.html.postMentioned',
        ];
    }

    public function getEmailSubject(TranslatorInterface $translator): string
    {
        return $translator->trans('flarum-mentions.email.post_mentioned.subject', [
            '{replier_display_name}' => $this->reply->user->display_name,
            '{title}' => $this->post->discussion->title
        ]);
    }

    public static function getType(): string
    {
        return 'postMentioned';
    }

    public static function getSubjectModel(): string
    {
        return Post::class;
    }
}
