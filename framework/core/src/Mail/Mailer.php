<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail;

use Flarum\Mail\Event\EmailSendFailed;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\UserRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;
use Illuminate\Mail\Mailer as IlluminateMailer;
use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;

class Mailer extends IlluminateMailer
{
    public function __construct(
        string $name,
        Factory $views,
        TransportInterface $transport,
        ?Dispatcher $events,
        protected SettingsRepositoryInterface $settings,
        protected LoggerInterface $logger,
        protected UserRepository $users,
    ) {
        parent::__construct($name, $views, $transport, $events);
    }

    public function send($view, array $data = [], $callback = null)
    {
        $emailType = $this->settings->get('mail_format');

        switch ($emailType) {
            case 'html':
                unset($view['text']);
                break;
            case 'plain':
                unset($view['html']);
                break;
                // case 'multipart' is the default, where Flarum will send both HTML and text versions of emails, so that the recipient's email client can choose which one to display.
        }

        try {
            return parent::send($view, $data, $callback);
        } catch (\Throwable $e) {
            $recipientEmail = Arr::get($data, 'userEmail');
            $recipientName = Arr::get($data, 'username');

            $this->logger->error('Failed to send email.', [
                'recipient_email' => $recipientEmail,
                'recipient_name' => $recipientName,
                'reason' => $e->getMessage(),
                'exception_class' => get_class($e),
            ]);

            $recipient = $recipientEmail ? $this->users->findByEmail($recipientEmail) : null;

            $this->events?->dispatch(new EmailSendFailed($recipientEmail, $recipientName, $e, $recipient));

            throw $e;
        }
    }
}
