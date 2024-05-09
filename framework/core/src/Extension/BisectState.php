<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extension;

use Flarum\Settings\SettingsRepositoryInterface;
use InvalidArgumentException;

class BisectState
{
    protected static SettingsRepositoryInterface $settings;
    public const SETTING = 'extension_bisect_state';

    public function __construct(
        public array $ids,
        public int $low,
        public int $high,
    ) {
    }

    public function advance(int $low, int $high): self
    {
        $this->low = $low;
        $this->high = $high;

        return $this->save();
    }

    public function save(): self
    {
        self::$settings->set(self::SETTING, json_encode($this->toArray()));

        return $this;
    }

    public function toArray(): array
    {
        return [
            'ids' => $this->ids,
            'low' => $this->low,
            'high' => $this->high,
        ];
    }

    public static function fromArray(array $data): BisectState
    {
        if (! isset($data['ids'], $data['low'], $data['high'])) {
            throw new InvalidArgumentException('Invalid data array');
        }

        return new self(
            $data['ids'],
            $data['low'],
            $data['high']
        );
    }

    public static function continue(): ?BisectState
    {
        $data = self::$settings->get(self::SETTING);

        if (! $data) {
            return null;
        }

        return self::fromArray(json_decode($data, true));
    }

    public static function continueOrStart(array $ids, int $low, int $high): BisectState
    {
        $state = self::continue();

        if ($state) {
            return $state;
        }

        return new self(
            $ids,
            $low,
            $high
        );
    }

    public static function end(): void
    {
        self::$settings->delete(self::SETTING);
    }

    public static function setSettings(SettingsRepositoryInterface $settings): void
    {
        self::$settings = $settings;
    }
}
