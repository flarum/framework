<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\frontend;

use Flarum\Foundation\Config;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

class FontAwesomeLoadingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure database is migrated
        $this->database();
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
            $this->assertStringContainsString("<link rel=\"preload\" href=\"$url\" as=\"font\" type=\"font/woff2\" crossorigin=\"\">", $body);
        }

        // Should not contain CDN or Kit URLs
        $this->assertStringNotContainsString('cdnjs.cloudflare.com', $body);
        $this->assertStringNotContainsString('kit.fontawesome.com', $body);
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

        // Should contain CDN CSS
        $this->assertStringContainsString('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">', $body);

        // Should not contain font preloads
        $this->assertStringNotContainsString('fa-solid-900.woff2', $body);
        $this->assertStringNotContainsString('fa-regular-400.woff2', $body);
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

        // Should contain Kit JS
        $this->assertStringContainsString('<script src="https://kit.fontawesome.com/abc123xyz.js"></script>', $body);

        // Should not contain font preloads
        $this->assertStringNotContainsString('fa-solid-900.woff2', $body);
        $this->assertStringNotContainsString('fa-regular-400.woff2', $body);
    }

    // Note: Config override tests are handled in unit tests for FontAwesome service
    // Integration tests for config overrides would require modifying the config
    // which is complex in the test environment

    #[Test]
    public function empty_cdn_url_does_not_load_anything()
    {
        $this->setting('fontawesome_source', 'cdn');
        $this->setting('fontawesome_cdn_url', '');

        $response = $this->send(
            $this->request('GET', '/')
        );

        $body = $response->getBody()->getContents();

        // Should not contain any CDN CSS (empty URL)
        $this->assertStringNotContainsString('cdnjs.cloudflare.com', $body);

        // Should not load local fonts either since source is CDN (just no URL provided)
        // This is expected behavior - misconfiguration results in no FontAwesome
        $this->assertStringNotContainsString('fa-solid-900.woff2', $body);
        $this->assertStringNotContainsString('fa-regular-400.woff2', $body);
    }

    #[Test]
    public function empty_kit_url_does_not_load_anything()
    {
        $this->setting('fontawesome_source', 'kit');
        $this->setting('fontawesome_kit_url', '');

        $response = $this->send(
            $this->request('GET', '/')
        );

        $body = $response->getBody()->getContents();

        // Should not contain any Kit JS (empty URL)
        $this->assertStringNotContainsString('kit.fontawesome.com', $body);

        // Should not load local fonts either since source is kit (just no URL provided)
        // This is expected behavior - misconfiguration results in no FontAwesome
        $this->assertStringNotContainsString('fa-solid-900.woff2', $body);
        $this->assertStringNotContainsString('fa-regular-400.woff2', $body);
    }

    #[Test]
    public function admin_panel_receives_fontawesome_config_override_flag()
    {
        // Without config override, should be false by default
        $response = $this->send(
            $this->request('GET', '/admin', [
                'authenticatedAs' => 1,
            ])
        );

        $body = $response->getBody()->getContents();
        $this->assertStringContainsString('"fontawesomeByConfig":false', $body);
    }
}
