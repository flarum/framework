<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\Transport\LogTransport;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Swift_Mailer;
use Swift_SendmailTransport;
use Swift_SmtpTransport;
use Swift_Transport;

class MailServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->app->singleton('swift.mailer', function ($app) {
            $settings = $app->make(SettingsRepositoryInterface::class);

            return new Swift_Mailer(
                $this->buildTransport($settings)
            );
        });

        $this->app->singleton('mailer', function ($app) {
            $mailer = new Mailer(
                $app['view'], $app['swift.mailer'], $app['events']
            );

            if ($app->bound('queue')) {
                $mailer->setQueue($app->make('queue'));
            }

            $settings = $app->make(SettingsRepositoryInterface::class);
            $mailer->alwaysFrom($settings->get('mail_from'), $settings->get('forum_title'));

            return $mailer;
        });
    }

    private function buildTransport(SettingsRepositoryInterface $settings): Swift_Transport
    {
        switch ($settings->get('mail_driver')) {
            case 'smtp':
                return $this->buildSmtpTransport($settings);
            case 'mail':
                return new Swift_SendmailTransport;
            case 'log':
                return new LogTransport($this->app->make(LoggerInterface::class));
            default:
                throw new InvalidArgumentException('Invalid mail driver configuration');
        }
    }

    private function buildSmtpTransport(SettingsRepositoryInterface $settings): Swift_Transport
    {
        $transport = new Swift_SmtpTransport(
            $settings->get('mail_host'),
            $settings->get('mail_port'),
            $settings->get('mail_encryption')
        );

        $transport->setUsername($settings->get('mail_username'));
        $transport->setPassword($settings->get('mail_password'));

        return $transport;
    }
}
