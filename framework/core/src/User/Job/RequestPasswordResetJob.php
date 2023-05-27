<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Job;

use Flarum\Http\UrlGenerator;
use Flarum\Locale\TranslatorInterface;
use Flarum\Mail\Job\SendRawEmailJob;
use Flarum\Queue\AbstractJob;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\PasswordToken;
use Flarum\User\UserRepository;
use Illuminate\Contracts\Queue\Queue;

class RequestPasswordResetJob extends AbstractJob
{
    public function __construct(
        protected string $email
    ) {
    }

    public function handle(
        SettingsRepositoryInterface $settings,
        UrlGenerator $url,
        TranslatorInterface $translator,
        UserRepository $users,
        Queue $queue
    ): void {
        $user = $users->findByEmail($this->email);

        if (! $user) {
            return;
        }

        $token = PasswordToken::generate($user->id);
        $token->save();

        $data = [
            'username' => $user->display_name,
            'url' => $url->to('forum')->route('resetPassword', ['token' => $token->token]),
            'forum' => $settings->get('forum_title'),
        ];

        $body = $translator->trans('core.email.reset_password.body', $data);
        $subject = $translator->trans('core.email.reset_password.subject');

        $queue->push(new SendRawEmailJob($user->email, $subject, $body));
    }
}
