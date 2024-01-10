<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Settings;

interface JsonSetting
{
    public function with(string $key, $value): self;

    public function save(): array;

    public function get(): array;

    public static function key(): string;

    public static function default(): array;
}
