<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Settings;

use Carbon\Carbon;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Support\Arr;

class LastUpdateCheck implements JsonSetting
{
    protected array $data = [];

    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function with(string $key, mixed $value): JsonSetting
    {
        $this->data[$key] = $value;

        return $this;
    }

    public function save(): array
    {
        $lastUpdateCheck = [
            'checkedAt' => Carbon::now(),
            'updates' => $this->data,
        ];

        $this->settings->set($this->key(), json_encode($lastUpdateCheck));

        return $lastUpdateCheck;
    }

    public function get(): array
    {
        return json_decode($this->settings->get($this->key()), true);
    }

    public static function key(): string
    {
        return 'flarum-extension-manager.last_update_check';
    }

    public static function default(): array
    {
        return [
            'checkedAt' => null,
            'updates' => [
                'installed' => [],
            ],
        ];
    }

    public function getNewMajorVersion(): ?string
    {
        $core = Arr::first(Arr::get($this->get(), 'updates.installed', []), function ($package) {
            return $package['name'] === 'flarum/core';
        });

        return $core ? $core['latest-major'] : null;
    }
}
