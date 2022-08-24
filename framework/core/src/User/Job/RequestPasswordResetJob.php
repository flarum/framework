<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Job;

use Flarum\Http\UrlGenerator;
use Flarum\Mail\Job\SendRawEmailJob;
use Flarum\Queue\AbstractJob;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\PasswordToken;
use Flarum\User\User;
use Illuminate\Contracts\Queue\Queue;
use Symfony\Contracts\Translation\TranslatorInterface;

class RequestPasswordResetJob extends AbstractJob
{
    /**
     * @var User
     */
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function handle(
        SettingsRepositoryInterface $settings,
        UrlGenerator $url,
        TranslatorInterface $translator,
        Queue $queue
    ) {
        $user = $this->user;
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
