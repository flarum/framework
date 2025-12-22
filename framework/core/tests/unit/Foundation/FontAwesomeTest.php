<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Foundation;

use Flarum\Foundation\Config;
use Flarum\Foundation\FontAwesome;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\Test;

class FontAwesomeTest extends TestCase
{
    private function mockSettings(array $data = []): SettingsRepositoryInterface
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')
            ->willReturnCallback(function ($key, $default = null) use ($data) {
                return $data[$key] ?? $default;
            });

        return $settings;
    }

    #[Test]
    public function it_defaults_to_local_source()
    {
        $config = new Config(['url' => 'https://example.com']);
        $settings = $this->mockSettings();

        $fontAwesome = new FontAwesome($config, $settings);

        $this->assertEquals(FontAwesome::SOURCE_LOCAL, $fontAwesome->source());
        $this->assertTrue($fontAwesome->useLocalFonts());
        $this->assertFalse($fontAwesome->useCdn());
        $this->assertFalse($fontAwesome->useKit());
    }

    #[Test]
    public function it_reads_source_from_settings()
    {
        $config = new Config(['url' => 'https://example.com']);
        $settings = $this->mockSettings([
            'fontawesome_source' => 'cdn',
        ]);

        $fontAwesome = new FontAwesome($config, $settings);

        $this->assertEquals(FontAwesome::SOURCE_CDN, $fontAwesome->source());
        $this->assertFalse($fontAwesome->useLocalFonts());
        $this->assertTrue($fontAwesome->useCdn());
        $this->assertFalse($fontAwesome->useKit());
    }

    #[Test]
    public function it_reads_kit_source_from_settings()
    {
        $config = new Config(['url' => 'https://example.com']);
        $settings = $this->mockSettings([
            'fontawesome_source' => 'kit',
        ]);

        $fontAwesome = new FontAwesome($config, $settings);

        $this->assertEquals(FontAwesome::SOURCE_KIT, $fontAwesome->source());
        $this->assertFalse($fontAwesome->useLocalFonts());
        $this->assertFalse($fontAwesome->useCdn());
        $this->assertTrue($fontAwesome->useKit());
    }

    #[Test]
    public function it_config_overrides_settings()
    {
        $config = new Config([
            'url' => 'https://example.com',
            'fontawesome' => [
                'source' => 'cdn',
            ],
        ]);
        $settings = $this->mockSettings([
            'fontawesome_source' => 'local', // This should be overridden
        ]);

        $fontAwesome = new FontAwesome($config, $settings);

        $this->assertEquals(FontAwesome::SOURCE_CDN, $fontAwesome->source());
        $this->assertTrue($fontAwesome->useCdn());
        $this->assertTrue($fontAwesome->configOverride());
    }

    #[Test]
    public function it_returns_no_config_override_when_not_set()
    {
        $config = new Config(['url' => 'https://example.com']);
        $settings = $this->mockSettings([
            'fontawesome_source' => 'cdn',
        ]);

        $fontAwesome = new FontAwesome($config, $settings);

        $this->assertFalse($fontAwesome->configOverride());
    }

    #[Test]
    public function it_reads_cdn_url_from_settings()
    {
        $config = new Config(['url' => 'https://example.com']);
        $settings = $this->mockSettings([
            'fontawesome_cdn_url' => 'https://cdn.example.com/fontawesome.css',
        ]);

        $fontAwesome = new FontAwesome($config, $settings);

        $this->assertEquals('https://cdn.example.com/fontawesome.css', $fontAwesome->cdnUrl());
    }

    #[Test]
    public function it_reads_cdn_url_from_config()
    {
        $config = new Config([
            'url' => 'https://example.com',
            'fontawesome' => [
                'cdn_url' => 'https://cdn.config.com/fontawesome.css',
            ],
        ]);
        $settings = $this->mockSettings([
            'fontawesome_cdn_url' => 'https://cdn.settings.com/fontawesome.css',
        ]);

        $fontAwesome = new FontAwesome($config, $settings);

        // Config should override settings
        $this->assertEquals('https://cdn.config.com/fontawesome.css', $fontAwesome->cdnUrl());
    }

    #[Test]
    public function it_returns_null_for_empty_cdn_url()
    {
        $config = new Config(['url' => 'https://example.com']);
        $settings = $this->mockSettings([
            'fontawesome_cdn_url' => '',
        ]);

        $fontAwesome = new FontAwesome($config, $settings);

        $this->assertNull($fontAwesome->cdnUrl());
    }

    #[Test]
    public function it_reads_kit_url_from_settings()
    {
        $config = new Config(['url' => 'https://example.com']);
        $settings = $this->mockSettings([
            'fontawesome_kit_url' => 'https://kit.fontawesome.com/abcd1234.js',
        ]);

        $fontAwesome = new FontAwesome($config, $settings);

        $this->assertEquals('https://kit.fontawesome.com/abcd1234.js', $fontAwesome->kitUrl());
    }

    #[Test]
    public function it_reads_kit_url_from_config()
    {
        $config = new Config([
            'url' => 'https://example.com',
            'fontawesome' => [
                'kit_url' => 'https://kit.fontawesome.com/config.js',
            ],
        ]);
        $settings = $this->mockSettings([
            'fontawesome_kit_url' => 'https://kit.fontawesome.com/settings.js',
        ]);

        $fontAwesome = new FontAwesome($config, $settings);

        // Config should override settings
        $this->assertEquals('https://kit.fontawesome.com/config.js', $fontAwesome->kitUrl());
    }

    #[Test]
    public function it_returns_null_for_empty_kit_url()
    {
        $config = new Config(['url' => 'https://example.com']);
        $settings = $this->mockSettings([
            'fontawesome_kit_url' => '',
        ]);

        $fontAwesome = new FontAwesome($config, $settings);

        $this->assertNull($fontAwesome->kitUrl());
    }

    #[Test]
    public function it_handles_complete_config_override_scenario()
    {
        $config = new Config([
            'url' => 'https://example.com',
            'fontawesome' => [
                'source' => 'kit',
                'kit_url' => 'https://kit.fontawesome.com/xyz789.js',
            ],
        ]);
        $settings = $this->mockSettings([
            'fontawesome_source' => 'local',
            'fontawesome_kit_url' => '',
        ]);

        $fontAwesome = new FontAwesome($config, $settings);

        $this->assertTrue($fontAwesome->configOverride());
        $this->assertEquals(FontAwesome::SOURCE_KIT, $fontAwesome->source());
        $this->assertTrue($fontAwesome->useKit());
        $this->assertEquals('https://kit.fontawesome.com/xyz789.js', $fontAwesome->kitUrl());
    }

    #[Test]
    public function it_handles_cdn_configuration()
    {
        $config = new Config([
            'url' => 'https://example.com',
            'fontawesome' => [
                'source' => 'cdn',
                'cdn_url' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css',
            ],
        ]);
        $settings = $this->mockSettings();

        $fontAwesome = new FontAwesome($config, $settings);

        $this->assertTrue($fontAwesome->configOverride());
        $this->assertEquals(FontAwesome::SOURCE_CDN, $fontAwesome->source());
        $this->assertTrue($fontAwesome->useCdn());
        $this->assertEquals('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css', $fontAwesome->cdnUrl());
    }
}
