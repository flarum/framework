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
use Illuminate\Contracts\Container\Container;

/**
 * The forum's web app manifest, which Android and Chrome read for the name and
 * icons of a shortcut added to the home screen. It opens the forum as an app,
 * in a window of its own with a splash screen, unless an admin turns that off,
 * and then in the browser.
 */
class WebManifest
{
    /**
     * A start page is a path on the forum: no white space or backslashes, and
     * no "." or ".." segments, which would climb out of it.
     */
    public const START_PAGE_PATTERN = '/^(?!.*[\s\\\\])(?!(.*\/)?(\.|%2e){1,2}([\/?#]|$))/i';

    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected Branding $branding,
        protected UrlGenerator $url,
        protected Container $container,
        protected ShortName $shortName,
        protected SplashScreen $splashScreen
    ) {
    }

    /**
     * Whether pages link the manifest: once there is an app icon for it to
     * list, or an extension has changed it.
     */
    public function isLinked(): bool
    {
        return $this->branding->appIconUrls() !== [] || $this->container->make('flarum.forum.manifest') !== [];
    }

    public function opensAsApp(): bool
    {
        return (bool) $this->settings->get('open_as_app');
    }

    public function build(): array
    {
        $base = $this->url->to('forum')->base().'/';
        $startPage = $this->startPage();

        $manifest = array_filter([
            // Browsers tell installed apps apart by their id, which is the
            // start URL when there is none: pin it to the forum's address, so
            // a start page doesn't turn existing installs into another app.
            'id' => $startPage === '' ? null : $base,
            'name' => $this->settings->get('forum_title'),
            'short_name' => $this->shortName->setting(),
            'description' => $this->settings->get('forum_description'),
            'start_url' => $base.$startPage,
            'scope' => $base,
            'display' => $this->opensAsApp() ? 'standalone' : 'browser',
            'theme_color' => $this->settings->get('theme_primary_color'),
            'background_color' => $this->splashScreen->background(),
            'icons' => $this->icons(),
        ], fn ($value) => $value !== null && $value !== '' && $value !== []);

        foreach ($this->container->make('flarum.forum.manifest') as $modifier) {
            $manifest = $modifier($manifest);
        }

        return $manifest;
    }

    /**
     * The page the app opens to, as a path after the forum's address.
     */
    protected function startPage(): string
    {
        $path = ltrim(trim((string) $this->settings->get('app_start_page')), '/');

        return preg_match(self::START_PAGE_PATTERN, $path) ? $path : '';
    }

    /**
     * @return list<array<string, string>>
     */
    protected function icons(): array
    {
        $urls = $this->branding->appIconUrls();
        $icons = [];

        foreach (['icon-192.png' => ['192x192'], 'icon-512.png' => ['512x512'], 'icon-maskable-512.png' => ['512x512', 'maskable']] as $name => $details) {
            if (isset($urls[$name])) {
                $icons[] = ['src' => $urls[$name], 'sizes' => $details[0], 'type' => 'image/png'] + (isset($details[1]) ? ['purpose' => $details[1]] : []);
            }
        }

        return $icons;
    }
}
