<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Notification;

use Flarum\Database\AbstractModel;
use Flarum\Discussion\Discussion;
use Flarum\Locale\TranslatorInterface;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\MailableInterface;
use Flarum\Post\Post;
use Flarum\User\User;

class NewPostBlueprint implements BlueprintInterface, MailableInterface
{
    public function __construct(
        public Post $post
    ) {
    }

    public function getSubject(): ?AbstractModel
    {
        return $this->post->discussion;
    }

    public function getFromUser(): ?User
    {
        return $this->post->user;
    }

    public function getData(): array
    {
        return ['postNumber' => (int) $this->post->number];
    }

    public function getEmailViews(): array
    {
        return [
            'text' => 'flarum-subscriptions::emails.plain.newPost',
            'html' => 'flarum-subscriptions::emails.html.newPost', ];
    }

    public function getEmailSubject(TranslatorInterface $translator): string
    {
        return $translator->trans('flarum-subscriptions.email.new_post.subject', ['{title}' => $this->post->discussion->title]);
    }

    public static function getType(): string
    {
        return 'newPost';
    }

    public static function getSubjectModel(): string
    {
        return Discussion::class;
    }
}
