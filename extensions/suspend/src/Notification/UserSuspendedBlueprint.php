<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Suspend\Notification;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Flarum\Database\AbstractModel;
use Flarum\Locale\TranslatorInterface;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\MailableInterface;
use Flarum\User\User;

class UserSuspendedBlueprint implements BlueprintInterface, MailableInterface
{
    public function __construct(
        public User $user
    ) {
    }

    public function getSubject(): ?AbstractModel
    {
        return $this->user;
    }

    public function getFromUser(): ?User
    {
        return null;
    }

    public function getData(): CarbonInterface
    {
        return Carbon::now();
    }

    public static function getType(): string
    {
        return 'userSuspended';
    }

    public static function getSubjectModel(): string
    {
        return User::class;
    }

    public function getEmailViews(): array
    {
        return [
            'text' => 'flarum-suspend::emails.plain.suspended',
            'html' => 'flarum-suspend::emails.html.suspended', ];
    }

    public function getEmailSubject(TranslatorInterface $translator): string
    {
        return $translator->trans('flarum-suspend.email.suspended.subject');
    }
}
