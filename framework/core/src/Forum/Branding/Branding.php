<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Branding;

use Flarum\Mail\EmailLogo;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Filesystem\Factory;

/**
 * Where the forum's logos and icons are. An image a theme sets with the Theme
 * extender takes the place of one an admin uploaded.
 */
class Branding
{
    protected Cloud $assets;

    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected StoredFiles $files,
        protected ThemeImages $theme,
        Factory $filesystemFactory
    ) {
        $this->assets = $filesystemFactory->disk('flarum-assets');
    }

    public function logoUrl(): ?string
    {
        return $this->image('logo', 'logo_path');
    }

    public function logoDarkModeUrl(): ?string
    {
        return $this->image('logo_dark_mode', 'logo_dark_mode_path');
    }

    /**
     * The email logo an admin uploaded, or a theme set: not what emails fall
     * back to without one, which EmailLogo resolves.
     */
    public function emailLogoUrl(): ?string
    {
        if ($this->theme->has('email_logo')) {
            return $this->theme->urls('email_logo')['email'] ?? null;
        }

        return $this->url($this->settings->get(EmailLogo::PATH_KEY));
    }

    /**
     * The favicon as an image a page can show: a PNG, or an uploaded .ico.
     */
    public function faviconUrl(): ?string
    {
        if ($this->theme->has('favicon')) {
            return $this->theme->urls('favicon')['png'] ?? null;
        }

        return $this->url($this->settings->get('favicon_path'))
            ?? $this->appIconUrls()['favicon.png'] ?? null;
    }

    public function faviconIcoUrl(): ?string
    {
        if ($this->theme->has('favicon')) {
            return $this->theme->urls('favicon')['ico'] ?? null;
        }

        if ($path = $this->settings->get('favicon_path')) {
            return $this->url($this->settings->get('favicon_ico_path'))
                ?? (str_ends_with($path, '.ico') ? $this->url($path) : null);
        }

        return $this->appIconUrls()['favicon.ico'] ?? null;
    }

    public function faviconSvgUrl(): ?string
    {
        return $this->theme->urls('favicon')['svg'] ?? null;
    }

    /**
     * @return array<string, string> file name => URL
     */
    public function appIconUrls(): array
    {
        if ($this->theme->has('app_icon')) {
            return $this->theme->urls('app_icon');
        }

        return array_map(fn (string $path) => $this->assets->url($path), $this->files->paths('app_icon_paths'));
    }

    protected function image(string $themeImage, string $settingKey): ?string
    {
        if ($this->theme->has($themeImage)) {
            return $this->theme->urls($themeImage)['image'] ?? null;
        }

        return $this->url($this->settings->get($settingKey));
    }

    protected function url(?string $path): ?string
    {
        return $path ? $this->assets->url($path) : null;
    }
}
