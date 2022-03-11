<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Testing\integration\Extend;

use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;

class SetSettingsBeforeBoot implements ExtenderInterface
{
    /**
     * IDs of extensions to boot.
     */
    protected $settings;

    public function __construct($settings)
    {
        $this->settings = $settings;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        if (count($this->settings)) {
            $settings = $container->make(SettingsRepositoryInterface::class);

            foreach ($this->settings as $key => $value) {
                $settings->set($key, $value);
            }
        }
    }
}
