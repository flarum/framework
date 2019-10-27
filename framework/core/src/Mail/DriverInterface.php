<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail;

use Flarum\Settings\SettingsRepositoryInterface;
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
     * Provide a list of required settings for this driver.
     */
    public function requiredFields(): array;

    /**
     * Build a mail transport based on Flarum's current settings.
     */
    public function buildTransport(SettingsRepositoryInterface $settings): Swift_Transport;
}
