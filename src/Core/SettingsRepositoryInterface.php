<?php

namespace Flarum\Core;

interface SettingsRepositoryInterface
{
    public function get($key, $default = null);

    public function set($key, $value);
}
