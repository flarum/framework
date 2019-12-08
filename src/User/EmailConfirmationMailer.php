<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Event\EmailChangeRequested;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Mail\Message;

class EmailConfirmationMailer
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var Mailer
     */
    protected $mailer;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @var Translator
     */
    protected $translator;

    public function __construct(SettingsRepositoryInterface $settings, Mailer $mailer, UrlGenerator $url, Translator $translator)
    {
        $this->settings = $settings;
        $this->mailer = $mailer;
        $this->url = $url;
        $this->translator = $translator;
    }

    public function handle(EmailChangeRequested $event)
    {
        $email = $event->email;
        $data = $this->getEmailData($event->user, $email);

        $body = $this->translator->trans('core.email.confirm_email.body', $data);

        $this->mailer->raw($body, function (Message $message) use ($email, $data) {
            $message->to($email);
            $message->subject('['.$data['{forum}'].'] '.$this->translator->trans('core.email.confirm_email.subject'));
        });
    }

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
     * @param string $email
     * @return array
     */
    protected function getEmailData(User $user, $email)
    {
        $token = $this->generateToken($user, $email);

        return [
            '{username}' => $user->display_name,
            '{url}' => $this->url->to('forum')->route('confirmEmail', ['token' => $token->token]),
            '{forum}' => $this->settings->get('forum_title')
        ];
    }
}
