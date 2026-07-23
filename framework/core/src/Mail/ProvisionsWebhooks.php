<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail;

use Flarum\Settings\SettingsRepositoryInterface;

/**
 * An optional add-on to {@see HandlesWebhooks} for drivers whose provider
 * exposes an API to register the webhook, so an admin never has to configure
 * it manually in the provider's dashboard.
 *
 * @public
 */
interface ProvisionsWebhooks
{
    /**
     * Register (or update) the delivery-event webhook with the provider,
     * pointing it at the given Flarum webhook URL. Must be idempotent.
     *
     * @throws \Exception if provisioning fails (e.g. bad credentials); the
     *         caller is responsible for surfacing the error to the admin.
     */
    public function registerWebhook(SettingsRepositoryInterface $settings, string $url): void;

    /**
     * Remove the webhook previously registered for the given Flarum webhook
     * URL. Should not throw if the webhook does not exist.
     */
    public function unregisterWebhook(SettingsRepositoryInterface $settings, string $url): void;
}
