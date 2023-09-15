<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\Http\UrlGenerator;
use Flarum\Locale\TranslatorInterface;
use Flarum\Mail\Job\SendRawEmailJob;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Event\EmailChangeRequested;
use Illuminate\Contracts\Queue\Queue;

class EmailConfirmationMailer
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected Queue $queue,
        protected UrlGenerator $url,
        protected TranslatorInterface $translator
    ) {
    }

    public function handle(EmailChangeRequested $event): void
    {
        $email = $event->email;
        $data = $this->getEmailData($event->user, $email);

        $body = $this->translator->trans('core.email.confirm_email.body', $data);
        $subject = $this->translator->trans('core.email.confirm_email.subject');

        $this->queue->push(new SendRawEmailJob($email, $subject, $body));
    }

    protected function generateToken(User $user, string $email): EmailToken
    {
        $token = EmailToken::generate($email, $user->id);
        $token->save();

        return $token;
    }

    protected function getEmailData(User $user, string $email): array
    {
        $token = $this->generateToken($user, $email);

        return [
            'username' => $user->display_name,
            'url' => $this->url->route('forum.confirmEmail', ['token' => $token->token]),
            'forum' => $this->settings->get('forum_title')
        ];
    }
}
