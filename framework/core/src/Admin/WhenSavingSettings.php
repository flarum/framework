<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Admin;

use Flarum\Frontend\AssetManager;
use Flarum\Locale\LocaleManager;
use Flarum\Settings\Event\Saved;

class WhenSavingSettings
{
    /**
     * Settings that should trigger JS cache clear when saved.
     *
     * @var string[]
     */
    protected array $resetJsCacheFor = ['maintenance_mode', 'safe_mode_extensions'];

    public function __construct(
        protected AssetManager $assets,
        protected LocaleManager $locales,
    ) {
    }

    public function afterSave(Saved $events): void
    {
        if (! $this->hasDirtySettings($events)) {
            return;
        }

        $this->assets->flushJs();
    }

    public function resetJsCacheFor(string|array $setting): void
    {
        $this->resetJsCacheFor = array_merge($this->resetJsCacheFor, (array) $setting);
    }

    protected function hasDirtySettings(Saved $event): bool
    {
        return array_intersect(array_keys($event->settings), $this->resetJsCacheFor) !== [];
    }
}
