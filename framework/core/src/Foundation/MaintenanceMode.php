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
    public const NONE = 'none';
    public const HIGH = 'high';
    public const LOW = 'low';
    public const SAFE = 'safe';

    public function __construct(
        protected readonly Config $config,
        protected readonly SettingsRepositoryInterface $settings
    ) {
    }

    public function inMaintenanceMode(): bool
    {
        return $this->inHighMaintenanceMode() || $this->inLowMaintenanceMode() || $this->inSafeMode();
    }

    public function inHighMaintenanceMode(): bool
    {
        return $this->mode() === self::HIGH;
    }

    public function inLowMaintenanceMode(): bool
    {
        return $this->mode() === self::LOW;
    }

    public function inSafeMode(): bool
    {
        return $this->mode() === self::SAFE;
    }

    public function mode(): string
    {
        $mode = $this->config->maintenanceMode();

        if ($mode === self::NONE) {
            $mode = strval($this->settings->get('maintenance_mode', self::NONE));

            // Cannot set high maintenance mode from the settings.
            if ($mode === self::HIGH) {
                $mode = self::NONE;
            }
        }

        return $mode;
    }

    /** @return string[] */
    public function safeModeExtensions(): array
    {
        $extensions = $this->config->safeModeExtensions();

        if ($extensions === null) {
            $extensions = json_decode($this->settings->get('safe_mode_extensions', '[]'), true);
        }

        return $extensions;
    }

    public function configOverride(): bool
    {
        return $this->config->maintenanceMode() !== self::NONE;
    }
}
