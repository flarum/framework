<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Branding;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Filesystem\Factory;

/**
 * The uploaded app icon. Its source is kept, so the home screen icons can be
 * drawn again when the background colour changes.
 */
class AppIcon
{
    /**
     * The longest side the source is kept at: twice the largest icon drawn from it.
     */
    public const SOURCE_SIZE = 1024;

    protected Cloud $assets;

    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected StoredFiles $files,
        protected IconSet $icons,
        Factory $filesystemFactory
    ) {
        $this->assets = $filesystemFactory->disk('flarum-assets');
    }

    public function draw(): void
    {
        $source = $this->settings->get('app_icon_path');

        if (! $source || ! $this->assets->exists($source)) {
            return;
        }

        $this->files->replaceSet(
            'app_icon_paths',
            $this->icons->appIcon((string) $this->assets->get($source), $this->background())
        );
    }

    public function delete(): void
    {
        $this->files->delete('app_icon_path');
        $this->files->deleteSet('app_icon_paths');
    }

    public function background(): string
    {
        return $this->settings->get('app_icon_background') ?: '#ffffff';
    }
}
