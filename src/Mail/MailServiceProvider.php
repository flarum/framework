<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Arr;
use Swift_Mailer;

class MailServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->container->singleton('mail.supported_drivers', function () {
            return [
                'mail' => SendmailDriver::class,
                'mailgun' => MailgunDriver::class,
                'log' => LogDriver::class,
                'smtp' => SmtpDriver::class,
            ];
        });

        $this->container->singleton('mail.driver', function () {
            $configured = $this->container->make('flarum.mail.configured_driver');
            $settings = $this->container->make(SettingsRepositoryInterface::class);
            $validator = $this->container->make(Factory::class);

            return $configured->validate($settings, $validator)->any()
                ? $this->container->make(NullDriver::class)
                : $configured;
        });

        $this->container->alias('mail.driver', DriverInterface::class);

        $this->container->singleton('flarum.mail.configured_driver', function () {
            $drivers = $this->container->make('mail.supported_drivers');
            $settings = $this->container->make(SettingsRepositoryInterface::class);
            $driverName = $settings->get('mail_driver');

            $driverClass = Arr::get($drivers, $driverName);

            return $driverClass
                ? $this->container->make($driverClass)
                : $this->container->make(NullDriver::class);
        });

        $this->container->singleton('swift.mailer', function ($container) {
            return new Swift_Mailer(
                $container->make('mail.driver')->buildTransport(
                    $container->make(SettingsRepositoryInterface::class)
                )
            );
        });

        $this->container->singleton('mailer', function ($container) {
            $mailer = new Mailer(
                'flarum',
                $container['view'],
                $container['swift.mailer'],
                $container['events']
            );

            if ($container->bound('queue')) {
                $mailer->setQueue($container->make('queue'));
            }

            $settings = $container->make(SettingsRepositoryInterface::class);
            $mailer->alwaysFrom($settings->get('mail_from'), $settings->get('forum_title'));

            return $mailer;
        });
    }
}
