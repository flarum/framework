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
 * icons of a shortcut added to the home screen. It opens the forum in the
 * browser: running as an installed app is left to extensions that add what an
 * app needs, such as offline support.
 */
class WebManifest
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected Branding $branding,
        protected AppIcon $appIcon,
        protected UrlGenerator $url,
        protected Container $container,
        protected ShortName $shortName
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

    public function build(): array
    {
        $base = $this->url->to('forum')->base().'/';

        $manifest = array_filter([
            'name' => $this->settings->get('forum_title'),
            'short_name' => $this->shortName->setting(),
            'description' => $this->settings->get('forum_description'),
            'start_url' => $base,
            'scope' => $base,
            'display' => 'browser',
            'theme_color' => $this->settings->get('theme_primary_color'),
            'background_color' => $this->appIcon->background(),
            'icons' => $this->icons(),
        ], fn ($value) => $value !== null && $value !== '' && $value !== []);

        foreach ($this->container->make('flarum.forum.manifest') as $modifier) {
            $manifest = $modifier($manifest);
        }

        return $manifest;
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
