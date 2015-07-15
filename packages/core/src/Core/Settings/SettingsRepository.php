<?php

namespace Flarum\Core\Settings;

interface SettingsRepository
{
    public function all();

    public function get($key, $default = null);

    public function set($key, $value);
}
