<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail;

use Flarum\Forum\ValidateMailConfiguration;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Mail\Mailer;
use Swift_Mailer;

class MailServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->app->singleton('mail.supported_drivers', function () {
            return [
                'mail' => SendmailDriver::class,
                'mailgun' => MailgunDriver::class,
                'mandrill' => MandrillDriver::class,
                'log' => LogDriver::class,
                'ses' => SesDriver::class,
                'smtp' => SmtpDriver::class,
            ];
        });

        $this->app->singleton('mail.driver', function () {
            return $this->app->make(ValidateMailConfiguration::class)->getWorkingDriver();
        });

        $this->app->alias('mail.driver', DriverInterface::class);

        $this->app->singleton('swift.mailer', function ($app) {
            return new Swift_Mailer(
                $app->make('mail.driver')->buildTransport(
                    $app->make(SettingsRepositoryInterface::class)
                )
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
}
