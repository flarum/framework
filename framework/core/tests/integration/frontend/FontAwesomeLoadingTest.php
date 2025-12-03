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

        // Should contain CDN CSS with crossorigin attribute
        $this->assertStringContainsString('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous">', $body);

        // Should contain preload for CDN CSS
        $this->assertStringContainsString('<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" as="style" crossorigin="anonymous">', $body);

        // Should not contain local font preloads
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

        // Should contain Kit JS with crossorigin attribute
        $this->assertStringContainsString('<script src="https://kit.fontawesome.com/abc123xyz.js" crossorigin="anonymous"></script>', $body);

        // Should contain preload for Kit JS
        $this->assertStringContainsString('<link rel="preload" href="https://kit.fontawesome.com/abc123xyz.js" as="script" crossorigin="anonymous">', $body);

        // Should not contain local font preloads
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

        // Should use config CDN URL with crossorigin attribute, not local fonts
        $this->assertStringContainsString('<link rel="stylesheet" href="https://config.example.com/fontawesome.css" crossorigin="anonymous">', $body);

        // Should contain preload for config CDN CSS
        $this->assertStringContainsString('<link rel="preload" href="https://config.example.com/fontawesome.css" as="style" crossorigin="anonymous">', $body);

        // Should not contain local font preloads
        $this->assertStringNotContainsString('fa-solid-900.woff2', $body);
        $this->assertStringNotContainsString('fa-regular-400.woff2', $body);
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

        // Should use config Kit URL with crossorigin attribute
        $this->assertStringContainsString('<script src="https://kit.fontawesome.com/config123.js" crossorigin="anonymous"></script>', $body);

        // Should contain preload for config Kit JS
        $this->assertStringContainsString('<link rel="preload" href="https://kit.fontawesome.com/config123.js" as="script" crossorigin="anonymous">', $body);

        // Should not contain database CDN URL
        $this->assertStringNotContainsString('database.example.com', $body);

        // Should not contain local font preloads
        $this->assertStringNotContainsString('fa-solid-900.woff2', $body);
        $this->assertStringNotContainsString('fa-regular-400.woff2', $body);
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
            $this->assertStringContainsString("<link rel=\"preload\" href=\"$url\" as=\"font\" type=\"font/woff2\" crossorigin=\"\">", $body);
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
