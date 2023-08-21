<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification;

use Flarum\Http\UrlGenerator;
use Flarum\Locale\TranslatorInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Message;
use Illuminate\Support\Arr;

class NotificationMailer
{
    public function __construct(
        protected Mailer $mailer,
        protected TranslatorInterface $translator,
        protected SettingsRepositoryInterface $settings,
        protected UrlGenerator $url
    ) {
    }

    public function send(MailableInterface $blueprint, User $user): void
    {
        // Ensure that notifications are delivered to the user in their default language, if they've selected one.
        $this->translator->setLocale($user->getPreference('locale') ?? $this->settings->get('default_locale'));

        // Generate and save the unsubscribe token:
        $unsubscribeRecord = UnsubscribeToken::generate($user->id, $blueprint::getType());
        $unsubscribeRecord->save();

        $unsubscribeLink = $this->url->to('forum')->route('notifications.unsubscribe', ['userId' => $user->id, 'token' => $unsubscribeRecord->token]);
        $settingsLink = $this->url->to('forum')->route('settings');

        $this->mailer->send(
            $this->getEmailViews($blueprint),
            compact('blueprint', 'user', 'unsubscribeLink', 'settingsLink'),
            function (Message $message) use ($blueprint, $user) {
                $message->to($user->email, $user->display_name)
                        ->subject($blueprint->getEmailSubject($this->translator));
                //->getHeaders()
                //->addTextHeader('List-Unsubscribe', '<'.$unsubscribeLink.'>');
                //->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            }
        );
    }

    /**
     * Retrives the email views from the blueprint, and enforces that both a
     * plain text and HTML view are provided.
     *
     * @param MailableInterface $blueprint
     * @return array{
     *     text: string,
     *     html: string
     * }
     */
    protected function getEmailViews(MailableInterface $blueprint): array
    {
        $views = $blueprint->getEmailViews();

        // check that both text and html views are provided
        if (! Arr::has($views, ['text', 'html'])) {
            throw new \InvalidArgumentException('Both text and html views must be provided to send an email notification of type'.$blueprint::getType().'.');
        }

        return $views;
    }
}
