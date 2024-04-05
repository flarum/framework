<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Flarum\Settings\SettingsRepositoryInterface;

class MaintenanceMode
{
    public const NONE = 0;
    public const HIGH = 1;
    public const LOW = 2;

    public function __construct(
        protected readonly Config $config,
        protected readonly SettingsRepositoryInterface $settings
    ) {
    }

    public function inMaintenanceMode(): bool
    {
        return $this->inHighMaintenanceMode() || $this->inLowMaintenanceMode();
    }

    public function inHighMaintenanceMode(): bool
    {
        return $this->mode() === self::HIGH;
    }

    public function inLowMaintenanceMode(): bool
    {
        return $this->mode() === self::LOW;
    }

    public function mode(): int
    {
        $mode = $this->config->maintenanceMode();

        if ($mode === self::NONE) {
            $mode = intval($this->settings->get('maintenance_mode', self::NONE));

            // Cannot set high maintenance mode from the settings.
            if ($mode === self::HIGH) {
                $mode = self::NONE;
            }
        }

        return $mode;
    }

    public function configOverride(): bool
    {
        return $this->config->maintenanceMode() !== self::NONE;
    }
}
