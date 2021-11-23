<?php

namespace Flarum\PackageManager\Settings;

interface JsonSetting
{
    public function with(string $key, $value): self;

    public function save(): array;

    public function get(): array;

    public static function key(): string;

    public static function default(): array;
}
