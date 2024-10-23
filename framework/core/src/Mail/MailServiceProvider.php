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
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Arr;
use Symfony\Component\Mailer\Transport\TransportInterface;

class MailServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->singleton('mail.supported_drivers', function () {
            return [
                'mail' => SendmailDriver::class,
                'mailgun' => MailgunDriver::class,
                'log' => LogDriver::class,
                'smtp' => SmtpDriver::class,
            ];
        });

        $this->container->singleton('mail.driver', function (Container $container) {
            $configured = $container->make('flarum.mail.configured_driver');
            $settings = $container->make(SettingsRepositoryInterface::class);
            $validator = $container->make(Factory::class);

            return $configured->validate($settings, $validator)->any()
                ? $container->make(NullDriver::class)
                : $configured;
        });

        $this->container->alias('mail.driver', DriverInterface::class);

        $this->container->singleton('flarum.mail.configured_driver', function (Container $container) {
            $drivers = $container->make('mail.supported_drivers');
            $settings = $container->make(SettingsRepositoryInterface::class);
            $driverName = $settings->get('mail_driver');

            $driverClass = Arr::get($drivers, $driverName);

            return $driverClass
                ? $container->make($driverClass)
                : $container->make(NullDriver::class);
        });

        $this->container->singleton('symfony.mailer.transport', function (Container $container): TransportInterface {
            return $container->make('mail.driver')->buildTransport(
                $container->make(SettingsRepositoryInterface::class)
            );
        });

        $this->container->singleton('mailer', function (Container $container): MailerContract {
            $settings = $container->make(SettingsRepositoryInterface::class);

            $mailer = new Mailer(
                'flarum',
                $container['view'],
                $container['symfony.mailer.transport'],
                $container['events'],
                $settings,
            );

            if ($container->bound('queue')) {
                $mailer->setQueue($container->make('queue'));
            }

            $mailer->alwaysFrom($settings->get('mail_from'), $settings->get('forum_title'));

            return $mailer;
        });

        $this->container->alias('mailer', MailerContract::class);
    }

    public function boot(Dispatcher $events): void
    {
        $events->listen(MessageSending::class, MutateEmail::class);
    }
}
