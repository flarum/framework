<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\MessageBag;
use Swift_Transport;

/**
 * An interface for a mail service.
 *
 * This interface provides all methods necessary for configuring, checking and
 * using one of Laravel's various email drivers throughout Flarum.
 *
 * @public
 */
interface DriverInterface
{
    /**
     * Provide a list of settings for this driver.
     *
     * The list must be an array of field names (keys) mapping to their type
     * (the empty string "" for a text field; or an array of possible values for
     * a dropdown field).
     */
    public function availableSettings(): array;

    /**
     * Ensure the given settings are enough to send emails.
     *
     * This method is responsible for determining whether the user-provided
     * values stored in Flarum's settings are "valid" as far as a simple
     * inspection of these values can determine it. Of course, this does not
     * mean that the mail server or API will actually accept e.g. credentials.
     *
     * Any errors must be wrapped in a {@see \Illuminate\Support\MessageBag}.
     * If there are no errors, an empty instance can be returned. In the
     * presence of validation problems with the configured mail driver, Flarum
     * will fall back to its no-op {@see \Flarum\Mail\NullDriver}.
     */
    public function validate(SettingsRepositoryInterface $settings, Factory $validator): MessageBag;

    /**
     * Does this driver actually send out emails?
     */
    public function canSend(): bool;

    /**
     * Build a mail transport based on Flarum's current settings.
     */
    public function buildTransport(SettingsRepositoryInterface $settings): Swift_Transport;
}
