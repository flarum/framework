<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Settings;

use Carbon\Carbon;
use Flarum\ExtensionManager\Event\FlarumUpdated;
use Flarum\Settings\SettingsRepositoryInterface;

class LastUpdateRun implements JsonSetting
{
    public const SUCCESS = 'success';
    public const FAILURE = 'failure';

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    protected $data;

    /**
     * @var string|null
     */
    protected $activeUpdate;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
        $this->data = self::default();
    }

    public function for(string $update): self
    {
        if (! in_array($update, [FlarumUpdated::MAJOR, FlarumUpdated::MINOR, FlarumUpdated::GLOBAL])) {
            throw new \InvalidArgumentException('Last update runs can only be for one of: minor, major, global');
        }

        $this->activeUpdate = $update;

        return $this;
    }

    public function with(string $key, $value): JsonSetting
    {
        $this->data[$this->activeUpdate][$key] = $value;

        return $this;
    }

    public function save(): array
    {
        $this->data[$this->activeUpdate]['ranAt'] = Carbon::now();

        $this->settings->set(self::key(), json_encode($this->data));

        return $this->data;
    }

    public function get(): array
    {
        $lastUpdateRun = json_decode($this->settings->get(self::key()), true);

        if ($this->activeUpdate) {
            return $lastUpdateRun[$this->activeUpdate];
        }

        return $lastUpdateRun;
    }

    public static function key(): string
    {
        return 'flarum-extension-manager.last_update_run';
    }

    public static function default(): array
    {
        $defaultState = [
            'ranAt' => null,
            'status' => null,
            'limitedPackages' => [],
            'incompatibleExtensions' => [],
        ];

        return [
            FlarumUpdated::GLOBAL => $defaultState,
            FlarumUpdated::MINOR => $defaultState,
            FlarumUpdated::MAJOR => $defaultState,
        ];
    }
}
