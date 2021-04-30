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
    /**
     * @param User $user
     * @param string $email
     * @return EmailToken
     */
    protected function generateToken(User $user, $email)
    {
        $token = EmailToken::generate($email, $user->id);
        $token->save();

        return $token;
    }

    /**
     * Get the data that should be made available to email templates.
     *
     * @param User $user
     * @param EmailToken $token
     * @return array
     */
    protected function getEmailData(User $user, EmailToken $token)
    {
        return [
            'username' => $user->display_name,
            'url' => $this->url->to('forum')->route('confirmEmail', ['token' => $token->token]),
            'forum' => $this->settings->get('forum_title')
        ];
    }

    /**
     * @param User $user
     * @param array $data
     */
    protected function sendConfirmationEmail(User $user, $data)
    {
        $body = $this->translator->trans('core.email.activate_account.body', $data);
        $subject = $this->translator->trans('core.email.activate_account.subject');

        $this->queue->push(new SendRawEmailJob($user->email, $subject, $body));
    }
}
