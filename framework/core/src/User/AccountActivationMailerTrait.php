<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\Mail\Job\SendRawEmailJob;

trait AccountActivationMailerTrait
{
    protected function generateToken(User $user, string $email): EmailToken
    {
        $token = EmailToken::generate($email, $user->id);
        $token->save();

        return $token;
    }

    /**
     * Get the data that should be made available to email templates.
     */
    protected function getEmailData(User $user, EmailToken $token): array
    {
        return [
            'username' => $user->display_name,
            'url' => $this->url->to('forum')->route('confirmEmail', ['token' => $token->token]),
            'forum' => $this->settings->get('forum_title')
        ];
    }

    protected function sendConfirmationEmail(User $user, array $data): void
    {
        $body = $this->translator->trans('core.email.activate_account.body', $data);
        $subject = $this->translator->trans('core.email.activate_account.subject');

        $this->queue->push(new SendRawEmailJob($user->email, $subject, $body));
    }
}
