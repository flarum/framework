<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Settings;

use Illuminate\Support\Collection;

class DefaultSettingsRepository implements SettingsRepositoryInterface
{
    protected $defaults;

    private $inner;

    public function __construct(SettingsRepositoryInterface $inner, Collection $defaults)
    {
        $this->inner = $inner;
        $this->defaults = $defaults;
    }

    public function get($key, $default = null)
    {
        // Global default overrules local default because local default is deprecated,
        // and will be removed in 2.0
        return $this->inner->get($key, $this->defaults->get($key, $default));
    }

    public function set($key, $value)
    {
        $this->inner->set($key, $value);
    }

    public function delete($keyLike)
    {
        $this->inner->delete($keyLike);
    }

    public function all(): array
    {
        return array_merge($this->defaults->toArray(), $this->inner->all());
    }
}
