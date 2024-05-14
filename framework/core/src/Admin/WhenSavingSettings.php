<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Admin;

use Flarum\Extension\Extension;
use Flarum\Extension\ExtensionManager;
use Flarum\Frontend\AssetManager;
use Flarum\Locale\LocaleManager;
use Flarum\Settings\Event\Saved;
use Flarum\Settings\Event\Saving;

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
        protected ExtensionManager $extensions,
    ) {
    }

    public function beforeSave(Saving $event): void
    {
        if (array_key_exists('safe_mode_extensions', $event->settings)) {
            $safeModeExtensions = json_decode($event->settings['safe_mode_extensions'] ?? '[]', true);

            $extensions = $this->extensions->getExtensions()->filter(function ($extension) use ($safeModeExtensions) {
                return in_array($extension->getId(), $safeModeExtensions);
            });

            $sorted = array_map(fn (Extension $e) => $e->getId(), $this->extensions->sortDependencies($extensions->all()));

            $event->settings['safe_mode_extensions'] = json_encode(array_values($sorted));
        }
    }

    public function afterSave(Saved $event): void
    {
        $this->resetCache($event);
    }

    protected function resetCache(Saved $event): void
    {
        if (! $this->hasDirtySettings($event)) {
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
