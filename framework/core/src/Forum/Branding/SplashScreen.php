<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Branding;

use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Filesystem\Factory;

/**
 * The splash screen phones show while the forum opens from the home screen as
 * an app: the app icon on its background colour, or on iPhones and iPads,
 * images a theme sets. There is one once there is an app icon or a theme's
 * images, unless an admin turns off opening as an app.
 *
 * Android draws it, for a forum that opens as an app, which the web app
 * manifest then asks for. It can't be given an image of its own: it picks one
 * of the app icon's files.
 *
 * iPhones and iPads show only images drawn for the exact size of the screen,
 * in its orientation, so pages link one for every screen, drawn like Android's.
 * Each is drawn the first time it is asked for, which is when someone adds the
 * forum to their home screen, and drawn again once the app icon or its
 * background colour changes.
 *
 * Desktop browsers show none.
 */
class SplashScreen
{
    /**
     * The width and height of each iPhone and iPad screen held upright, in
     * points, and how many pixels make up a point. Keep the screens of older
     * devices: without theirs, they show no splash screen.
     *
     * @var list<array{0: int, 1: int, 2: int}>
     */
    public const SCREENS = [
        [320, 568, 2],
        [375, 667, 2],
        [414, 736, 3],
        [375, 812, 3],
        [414, 896, 2],
        [414, 896, 3],
        [390, 844, 3],
        [428, 926, 3],
        [393, 852, 3],
        [430, 932, 3],
        [402, 874, 3],
        [440, 956, 3],
        [420, 912, 3],
        [768, 1024, 2],
        [810, 1080, 2],
        [820, 1180, 2],
        [744, 1133, 2],
        [834, 1112, 2],
        [834, 1194, 2],
        [834, 1210, 2],
        [1024, 1366, 2],
        [1032, 1376, 2],
        [466, 678, 3],
    ];

    /**
     * Raise to draw every splash screen again, after changing how they are drawn.
     */
    protected const VERSION = 1;

    protected Cloud $assets;

    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected Branding $branding,
        protected IconSet $icons,
        protected UrlGenerator $url,
        Factory $filesystemFactory
    ) {
        $this->assets = $filesystemFactory->disk('flarum-assets');
    }

    /**
     * The links for a page's head, one for each screen in each orientation.
     *
     * @return list<array{href: string, media: string}>
     */
    public function links(): array
    {
        $version = $this->version();

        $this->forgetOthers($version);

        if ($version === null) {
            return [];
        }

        $links = [];

        foreach (self::SCREENS as [$width, $height, $ratio]) {
            foreach (['portrait' => [$width, $height], 'landscape' => [$height, $width]] as $orientation => [$across, $down]) {
                $links[] = [
                    'href' => $this->address($version, $across * $ratio, $down * $ratio),
                    // iOS gives a screen's upright width and height in either orientation.
                    'media' => "(device-width: {$width}px) and (device-height: {$height}px) and (-webkit-device-pixel-ratio: $ratio) and (orientation: $orientation)",
                ];
            }
        }

        return $links;
    }

    /**
     * The address of the splash screen for a screen this many pixels across
     * and down.
     */
    public function url(int $width, int $height): ?string
    {
        $version = $this->version();

        return $version ? $this->address($version, $width, $height) : null;
    }

    /**
     * The splash screen for a screen this many pixels across and down, or null
     * if there is none.
     */
    public function image(int $width, int $height): ?string
    {
        $source = $this->source();
        $version = $this->version();
        $ratio = $this->sizes()["{$width}x$height"] ?? null;

        if ($source === null || $version === null || $ratio === null) {
            return null;
        }

        $this->forgetOthers($version);

        $path = $this->path($version, "{$width}x$height", $source['theme'] ? 'jpg' : 'png');

        if ($this->assets->exists($path)) {
            return $this->assets->get($path);
        }

        if ($source['theme']) {
            $file = $width > $height ? ($source['paths']['landscape'] ?? $source['paths']['portrait']) : $source['paths']['portrait'];
        } else {
            $file = $source['paths']['icon'];
        }

        $data = $this->assets->get($file);

        if (! $data) {
            return null;
        }

        $image = $source['theme']
            ? $this->icons->cover($data, $width, $height)
            : $this->icons->splash($data, $width, $height, $ratio, $this->background());

        $this->assets->put($path, $image, StoredFiles::options($path));

        return $image;
    }

    /**
     * Changes whenever the splash screens do, or null when there are none.
     */
    public function version(): ?string
    {
        $source = $this->source();

        return $source ? substr(md5(json_encode([self::VERSION, $source, $this->background()])), 0, 8) : null;
    }

    /**
     * The colour that fills the screen: the one set for the splash screen, or
     * else the one the app icon is drawn on.
     */
    public function background(): string
    {
        return $this->settings->get('splash_screen_background') ?: $this->branding->appIconBackground();
    }

    public function type(): string
    {
        return ($this->source()['theme'] ?? false) ? 'image/jpeg' : 'image/png';
    }

    /**
     * What the splash screens are drawn from: a theme's full-screen images,
     * or the maskable app icon, padded with its background colour, which is
     * the one Android shows.
     *
     * @return array{theme: bool, paths: array<string, string>}|null
     */
    protected function source(): ?array
    {
        if (! $this->settings->get('open_as_app')) {
            return null;
        }

        if ($images = $this->branding->splashScreenPaths()) {
            return ['theme' => true, 'paths' => $images];
        }

        $icon = $this->branding->appIconPaths()['icon-maskable-512.png'] ?? null;

        return $icon ? ['theme' => false, 'paths' => ['icon' => $icon]] : null;
    }

    /**
     * Deletes the splash screens drawn for an earlier app icon or background
     * colour.
     */
    protected function forgetOthers(?string $version): void
    {
        $drawn = $this->settings->get('splash_version');

        if ($drawn === $version) {
            return;
        }

        if ($drawn) {
            foreach (['png', 'jpg'] as $extension) {
                $this->assets->delete(array_map(fn (string $size) => $this->path($drawn, $size, $extension), array_keys($this->sizes())));
            }
        }

        $this->settings->set('splash_version', $version);
    }

    /**
     * @return array<string, int> pixels across and down, as "WIDTHxHEIGHT" => pixels to a point
     */
    protected function sizes(): array
    {
        $sizes = [];

        foreach (self::SCREENS as [$width, $height, $ratio]) {
            $sizes[($width * $ratio).'x'.($height * $ratio)] = $ratio;
            $sizes[($height * $ratio).'x'.($width * $ratio)] = $ratio;
        }

        return $sizes;
    }

    protected function address(string $version, int $width, int $height): string
    {
        return $this->url->to('forum')->route('splash', ['size' => "{$width}x$height"]).'?v='.$version;
    }

    protected function path(string $version, string $size, string $extension): string
    {
        return "splash-$version-$size.$extension";
    }
}
