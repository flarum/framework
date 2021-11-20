<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager;

use Carbon\Carbon;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class LastUpdateCheck
{
    public const KEY = 'flarum-package-manager.last_update_check';

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    public function save(array $updates): array
    {
        $lastUpdateCheck = [
            'checkedAt' => Carbon::now(),
            'updates' => $updates,
        ];

        $this->settings->set(self::KEY, json_encode($lastUpdateCheck));

        return $lastUpdateCheck;
    }

    public function get(): array
    {
        return json_decode($this->settings->get(self::KEY), true);
    }

    public function getNewMajorVersion(): ?string
    {
        $core = Arr::first(Arr::get($this->get(), 'updates.installed', []), function ($package) {
            return $package['name'] === 'flarum/core';
        });

        return $core ? $core['latest-major'] : null;
    }

    public function forget(string $name): void
    {
        $lastUpdateCheck = $this->get();

        if (isset($lastUpdateCheck['updates']) && ! empty($lastUpdateCheck['updates']['installed'])) {
            $updatesListChanged = false;

            foreach ($lastUpdateCheck['updates']['installed'] as $k => $package) {
                if ($package['name'] === $name) {
                    unset($lastUpdateCheck['updates']['installed'][$k]);
                    $updatesListChanged = true;
                    break;
                }
            }

            if ($updatesListChanged) {
                $lastUpdateCheck['updates']['installed'] = array_values($lastUpdateCheck['updates']['installed']);
                $this->settings->set(self::KEY, json_encode($lastUpdateCheck));
            }
        }
    }
}
