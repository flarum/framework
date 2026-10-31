<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\frontend;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class FontAwesomeLoadingTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser()
            ]
        ]);
    }

    #[Test]
    public function default_local_fontawesome_loads_font_preloads()
    {
        $response = $this->send(
            $this->request('GET', '/')
        );

        $filesystem = $this->app()->getContainer()->make('filesystem')->disk('flarum-assets');

        $urls = [
            $filesystem->url('fonts/fa-solid-900.woff2'),
            $filesystem->url('fonts/fa-regular-400.woff2'),
        ];

        $body = $response->getBody()->getContents();

        foreach ($urls as $url) {
            $this->assertMatchesRegularExpression('~<link rel="preload" href="'.preg_quote($url, '~').'(\?v=[0-9a-f]+)?" as="font" type="font/woff2" crossorigin="">~', $body);
        }

        // Should not contain CDN or Kit URLs
        $this->assertStringNotContainsString('cdnjs.cloudflare.com', $body);
        $this->assertStringNotContainsString('kit.fontawesome.com', $body);
    }

    #[Test]
    public function font_preloads_use_the_stylesheets_font_urls()
    {
        // A preload is only used when its URL is exactly the one the stylesheet
        // requests. The compiled CSS stamps each font URL with a revision
        // (`?v=…`), so a bare preload URL made every page fetch the fonts twice.
        $body = $this->send($this->request('GET', '/'))->getBody()->getContents();

        $assets = $this->app()->getContainer()->make('flarum.assets.forum');
        $compiler = $assets->makeCss();
        $compiler->commit(true);
        $css = $this->app()->getContainer()->make('filesystem')->disk('flarum-assets')->get($compiler->getFilename());

        foreach (['fa-solid-900.woff2', 'fa-regular-400.woff2'] as $file) {
            $this->assertMatchesRegularExpression('~url\("\./fonts/'.preg_quote($file, '~').'([^"]*)"\)~', $css);
            preg_match('~url\("\./fonts/'.preg_quote($file, '~').'([^"]*)"\)~', $css, $m);

            preg_match('~<link rel="preload" href="([^"]*/fonts/'.preg_quote($file, '~').'[^"]*)" as="font"~', $body, $preload);
            $this->assertNotEmpty($preload, "no preload for $file");

            $this->assertStringEndsWith('fonts/'.$file.$m[1], $preload[1], "the preload for $file is not the URL the stylesheet uses");
        }
    }

    #[Test]
    public function the_font_revision_is_cached()
    {
        $assets = $this->app()->getContainer()->make('flarum.assets.forum');
        $revision = $assets->fontRevision('fa-solid-900.woff2');

        $this->assertNotNull($revision);

        // The preloads are built on every page view; the second lookup must come
        // from the cache, not from hashing the font again.
        $events = new \Illuminate\Events\Dispatcher();
        $hits = [];
        $events->listen(\Illuminate\Cache\Events\CacheHit::class, function ($event) use (&$hits) {
            $hits[] = $event->key;
        });
        $this->app()->getContainer()->make('cache.store')->setEventDispatcher($events);

        $this->assertSame($revision, $assets->fontRevision('fa-solid-900.woff2'));
        $this->assertNotEmpty(array_filter($hits, fn ($key) => str_starts_with($key, 'flarum.frontend.font_revision.')));
    }

    #[Test]
    public function fontawesome_cdn_loads_css_instead_of_local_fonts()
    {
        $this->setting('fontawesome_source', 'cdn');
        $this->setting('fontawesome_cdn_url', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css');

        $response = $this->send(
            $this->request('GET', '/')
        );

        $body = $response->getBody()->getContents();

        // Should load CDN CSS asynchronously (preload + onload swap)
        $this->assertStringContainsString('<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" as="style" crossorigin="anonymous" onload="this.onload=null;this.rel=\'stylesheet\'" onerror="window.flarumRescueIconFonts&&window.flarumRescueIconFonts()">', $body);

        // No `<noscript>` counterpart. Icons are rendered by the SPA, so with
        // scripting off the page contains no icon elements for a stylesheet to
        // style — the fallback only ever cost a request.
        $this->assertStringNotContainsString('<noscript><link rel="stylesheet"', $body);

        // Should not preload the local fonts. (Their filenames do appear in the
        // rescue payload, which rebinds to them if the remote source fails.)
        $this->assertStringNotContainsString('as="font"', $body);
    }

    #[Test]
    public function fontawesome_kit_loads_js_instead_of_local_fonts()
    {
        $this->setting('fontawesome_source', 'kit');
        $this->setting('fontawesome_kit_url', 'https://kit.fontawesome.com/abc123xyz.js');

        $response = $this->send(
            $this->request('GET', '/')
        );

        $body = $response->getBody()->getContents();

        // Should load Kit JS deferred (not blocking)
        $this->assertStringContainsString('<script src="https://kit.fontawesome.com/abc123xyz.js" crossorigin="anonymous" defer onerror="window.flarumRescueIconFonts&&window.flarumRescueIconFonts()"></script>', $body);

        // Should not preload the local fonts. (Their filenames do appear in the
        // rescue payload, which rebinds to them if the remote source fails.)
        $this->assertStringNotContainsString('as="font"', $body);
    }

    // Note: Config override tests are handled in unit tests for FontAwesome service
    // Integration tests for config overrides would require modifying the config
    // which is complex in the test environment

    #[Test]
    public function empty_cdn_url_preloads_the_bundled_fonts_it_falls_back_to()
    {
        $this->setting('fontawesome_source', 'cdn');
        $this->setting('fontawesome_cdn_url', '');

        $response = $this->send(
            $this->request('GET', '/')
        );

        $body = $response->getBody()->getContents();

        // Should not contain any CDN CSS (empty URL)
        $this->assertStringNotContainsString('cdnjs.cloudflare.com', $body);

        // With no URL the source can't deliver anything, so the stylesheet uses
        // the bundled fonts (FontAwesome::needsBundledFonts()), and they're preloaded.
        $this->assertMatchesRegularExpression('~<link rel="preload" href="[^"]*/fonts/fa-solid-900\.woff2\?v=[0-9a-f]+" as="font"~', $body);
        $this->assertMatchesRegularExpression('~<link rel="preload" href="[^"]*/fonts/fa-regular-400\.woff2\?v=[0-9a-f]+" as="font"~', $body);
    }

    #[Test]
    public function empty_kit_url_preloads_the_bundled_fonts_it_falls_back_to()
    {
        $this->setting('fontawesome_source', 'kit');
        $this->setting('fontawesome_kit_url', '');

        $response = $this->send(
            $this->request('GET', '/')
        );

        $body = $response->getBody()->getContents();

        // Should not contain any Kit JS (empty URL)
        $this->assertStringNotContainsString('kit.fontawesome.com', $body);

        // With no URL the source can't deliver anything, so the stylesheet uses
        // the bundled fonts (FontAwesome::needsBundledFonts()), and they're preloaded.
        $this->assertMatchesRegularExpression('~<link rel="preload" href="[^"]*/fonts/fa-solid-900\.woff2\?v=[0-9a-f]+" as="font"~', $body);
        $this->assertMatchesRegularExpression('~<link rel="preload" href="[^"]*/fonts/fa-regular-400\.woff2\?v=[0-9a-f]+" as="font"~', $body);
    }

    #[Test]
    public function config_override_takes_precedence_over_database_settings()
    {
        // Set database settings to use local
        $this->setting('fontawesome_source', 'local');

        // But config.php is set to use CDN
        $this->config('fontawesome', [
            'source' => 'cdn',
            'cdn_url' => 'https://config.example.com/fontawesome.css',
        ]);

        $response = $this->send(
            $this->request('GET', '/')
        );

        $body = $response->getBody()->getContents();

        // Should load config CDN CSS asynchronously (preload + onload swap)
        $this->assertStringContainsString('<link rel="preload" href="https://config.example.com/fontawesome.css" as="style" crossorigin="anonymous" onload="this.onload=null;this.rel=\'stylesheet\'" onerror="window.flarumRescueIconFonts&&window.flarumRescueIconFonts()">', $body);

        // No `<noscript>` counterpart — see the CDN test above.
        $this->assertStringNotContainsString('<noscript><link rel="stylesheet"', $body);

        // Should not preload the local fonts. (Their filenames do appear in the
        // rescue payload, which rebinds to them if the remote source fails.)
        $this->assertStringNotContainsString('as="font"', $body);
    }

    #[Test]
    public function config_kit_override_takes_precedence_over_database_cdn()
    {
        // Set database settings to use CDN
        $this->setting('fontawesome_source', 'cdn');
        $this->setting('fontawesome_cdn_url', 'https://database.example.com/fontawesome.css');

        // But config.php is set to use Kit
        $this->config('fontawesome', [
            'source' => 'kit',
            'kit_url' => 'https://kit.fontawesome.com/config123.js',
        ]);

        $response = $this->send(
            $this->request('GET', '/')
        );

        $body = $response->getBody()->getContents();

        // Should use config Kit URL with crossorigin attribute, deferred
        $this->assertStringContainsString('<script src="https://kit.fontawesome.com/config123.js" crossorigin="anonymous" defer onerror="window.flarumRescueIconFonts&&window.flarumRescueIconFonts()"></script>', $body);

        // Should not contain database CDN URL
        $this->assertStringNotContainsString('database.example.com', $body);

        // Should not preload the local fonts. (Their filenames do appear in the
        // rescue payload, which rebinds to them if the remote source fails.)
        $this->assertStringNotContainsString('as="font"', $body);
    }

    #[Test]
    public function config_local_override_takes_precedence_over_database_cdn()
    {
        // Set database settings to use CDN
        $this->setting('fontawesome_source', 'cdn');
        $this->setting('fontawesome_cdn_url', 'https://database.example.com/fontawesome.css');

        // But config.php is set to use local
        $this->config('fontawesome', [
            'source' => 'local',
        ]);

        $response = $this->send(
            $this->request('GET', '/')
        );

        $filesystem = $this->app()->getContainer()->make('filesystem')->disk('flarum-assets');

        $urls = [
            $filesystem->url('fonts/fa-solid-900.woff2'),
            $filesystem->url('fonts/fa-regular-400.woff2'),
        ];

        $body = $response->getBody()->getContents();

        // Should use local fonts from config
        foreach ($urls as $url) {
            $this->assertMatchesRegularExpression('~<link rel="preload" href="'.preg_quote($url, '~').'(\?v=[0-9a-f]+)?" as="font" type="font/woff2" crossorigin="">~', $body);
        }

        // Should not contain database CDN URL
        $this->assertStringNotContainsString('database.example.com', $body);
    }

    #[Test]
    public function admin_panel_receives_fontawesome_config_override_flag_when_config_set()
    {
        // Set config override
        $this->config('fontawesome', [
            'source' => 'cdn',
            'cdn_url' => 'https://config.example.com/fontawesome.css',
        ]);

        $response = $this->send(
            $this->request('GET', '/admin', [
                'authenticatedAs' => 1,
            ])
        );

        $body = $response->getBody()->getContents();

        // Should indicate config override is active
        $this->assertStringContainsString('"fontawesomeByConfig":true', $body);

        // Should include the actual config values
        $this->assertStringContainsString('"fontawesomeConfig":', $body);
        $this->assertStringContainsString('"source":"cdn"', $body);
        $this->assertStringContainsString('"cdn_url":"https:\/\/config.example.com\/fontawesome.css"', $body);
    }

    #[Test]
    public function admin_panel_shows_kit_url_when_configured_via_config()
    {
        // Set database to local but config to kit
        $this->setting('fontawesome_source', 'local');

        $this->config('fontawesome', [
            'source' => 'kit',
            'kit_url' => 'https://kit.fontawesome.com/config-kit.js',
        ]);

        $response = $this->send(
            $this->request('GET', '/admin', [
                'authenticatedAs' => 1,
            ])
        );

        $body = $response->getBody()->getContents();

        // Should indicate config override is active
        $this->assertStringContainsString('"fontawesomeByConfig":true', $body);

        // Should include the actual config values (kit, not local)
        $this->assertStringContainsString('"source":"kit"', $body);
        $this->assertStringContainsString('"kit_url":"https:\/\/kit.fontawesome.com\/config-kit.js"', $body);
    }
}
