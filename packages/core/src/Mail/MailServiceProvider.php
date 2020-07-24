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
        $this->app->singleton('mail.supported_drivers', function () {
            return [
                'mail' => SendmailDriver::class,
                'mailgun' => MailgunDriver::class,
                'log' => LogDriver::class,
                'smtp' => SmtpDriver::class,
            ];
        });

        $this->app->singleton('mail.driver', function () {
            $configured = $this->app->make('flarum.mail.configured_driver');
            $settings = $this->app->make(SettingsRepositoryInterface::class);
            $validator = $this->app->make(Factory::class);

            return $configured->validate($settings, $validator)->any()
                ? $this->app->make(NullDriver::class)
                : $configured;
        });

        $this->app->alias('mail.driver', DriverInterface::class);

        $this->app->singleton('flarum.mail.configured_driver', function () {
            $drivers = $this->app->make('mail.supported_drivers');
            $settings = $this->app->make(SettingsRepositoryInterface::class);
            $driverName = $settings->get('mail_driver');

            $driverClass = Arr::get($drivers, $driverName);

            return $driverClass
                ? $this->app->make($driverClass)
                : $this->app->make(NullDriver::class);
        });

        $this->app->singleton('swift.mailer', function ($app) {
            return new Swift_Mailer(
                $app->make('mail.driver')->buildTransport(
                    $app->make(SettingsRepositoryInterface::class)
                )
            );
        });

        $this->app->singleton('mailer', function ($app) {
            $mailer = new Mailer(
                $app['view'],
                $app['swift.mailer'],
                $app['events']
            );

            if ($app->bound('queue')) {
                $mailer->setQueue($app->make('queue'));
            }

            $settings = $app->make(SettingsRepositoryInterface::class);
            $mailer->alwaysFrom($settings->get('mail_from'), $settings->get('forum_title'));

            return $mailer;
        });
    }
}
