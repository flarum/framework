<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;
use Illuminate\Mail\Mailer as IlluminateMailer;
use Symfony\Component\Mailer\Transport\TransportInterface;

class Mailer extends IlluminateMailer
{
    public function __construct(
        string $name,
        Factory $views,
        TransportInterface $transport,
        ?Dispatcher $events,
        protected SettingsRepositoryInterface $settings
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

        parent::send($view, $data, $callback);
    }
}
