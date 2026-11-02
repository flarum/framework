<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\Notification;

use Flarum\Database\AbstractModel;
use Flarum\Discussion\Discussion;
use Flarum\Locale\TranslatorInterface;
use Flarum\Notification\AlertableInterface;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\MailableInterface;
use Flarum\User\User;

class IdeaShippedBlueprint implements BlueprintInterface, AlertableInterface, MailableInterface
{
    public function __construct(
        public Discussion $discussion
    ) {
    }

    /*
     * Nobody: the idea shipped, whoever moved it. Core matches notifications
     * on their sender too, so a sender would make a ship by one moderator and
     * a retraction, or a second ship by another, miss each other.
     */
    public function getFromUser(): ?User
    {
        return null;
    }

    public function getSubject(): ?AbstractModel
    {
        return $this->discussion;
    }

    /*
     * Nothing that changes between two ships of the same idea, so shipping it
     * again matches the earlier notification instead of sending a second.
     */
    public function getData(): mixed
    {
        return null;
    }

    public static function getType(): string
    {
        return 'ideationShipped';
    }

    public static function getSubjectModel(): string
    {
        return Discussion::class;
    }

    public function getEmailViews(): array
    {
        return [
            'text' => 'flarum-ideation::emails.plain.shipped',
            'html' => 'flarum-ideation::emails.html.shipped',
        ];
    }

    public function getEmailSubject(TranslatorInterface $translator): string
    {
        return $translator->trans('flarum-ideation.email.shipped.subject', [
            '{title}' => $this->discussion->title,
        ]);
    }
}
