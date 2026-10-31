<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail;

use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;

class MailWebhooks
{
    public function __construct(
        protected Container $container,
        protected SettingsRepositoryInterface $settings,
        protected UrlGenerator $url,
    ) {
    }

    /**
     * The active mail driver, if it can report bounces with the current settings.
     */
    public function driver(): ?HandlesWebhooks
    {
        $driver = $this->container->make('flarum.mail.configured_driver');

        return $driver instanceof HandlesWebhooks && $driver->webhooksAvailable($this->settings) ? $driver : null;
    }

    public function provisioner(): ?ProvisionsWebhooks
    {
        $driver = $this->driver();

        return $driver instanceof ProvisionsWebhooks ? $driver : null;
    }

    public function configured(): bool
    {
        return (bool) $this->driver()?->webhookConfigured($this->settings);
    }

    /**
     * The URL the provider sends webhooks to, with the basic auth credentials
     * of an install behind HTTP authentication when they are set.
     */
    public function url(): string
    {
        $url = $this->url->to('forum')->route('mailWebhook', ['driver' => $this->settings->get('mail_driver')]);

        $username = (string) $this->settings->get('mail_webhook_username');

        if ($username === '') {
            return $url;
        }

        $password = (string) $this->settings->get('mail_webhook_password');
        $credentials = rawurlencode($username).($password !== '' ? ':'.rawurlencode($password) : '');

        return preg_replace('#^([a-z][a-z0-9+.-]*://)#i', '$1'.$credentials.'@', $url, 1);
    }
}
