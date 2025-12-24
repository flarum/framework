<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Foundation;

use Flarum\Foundation\Config;
use Flarum\Foundation\MaintenanceMode;
use Flarum\Testing\unit\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

class ConfigTest extends TestCase
{
    #[Test]
    public function it_complains_when_base_url_is_missing()
    {
        $this->expectException(InvalidArgumentException::class);

        new Config([]);
    }

    #[Test]
    public function it_wraps_base_url_in_value_object()
    {
        $config = new Config([
            'url' => 'https://flarum.localhost/myforum/',
        ]);

        $url = $config->url();
        $this->assertEquals('https', $url->getScheme());
        $this->assertEquals('/myforum', $url->getPath()); // Note that trailing slashes are removed
        $this->assertEquals('https://flarum.localhost/myforum', (string) $url);
    }

    #[Test]
    public function it_has_a_helper_for_debug_mode()
    {
        $config = new Config([
            'url' => 'https://flarum.localhost',
            'debug' => false,
        ]);

        $this->assertFalse($config->inDebugMode());

        $config = new Config([
            'url' => 'https://flarum.localhost',
            'debug' => true,
        ]);

        $this->assertTrue($config->inDebugMode());
    }

    #[Test]
    public function it_turns_off_debug_mode_by_default()
    {
        $config = new Config([
            'url' => 'https://flarum.localhost',
        ]);

        $this->assertFalse($config->inDebugMode());
    }

    #[Test]
    public function it_has_a_helper_for_maintenance_mode()
    {
        $config = new Config([
            'url' => 'https://flarum.localhost',
            'offline' => false,
        ]);

        $this->assertFalse($config->inHighMaintenanceMode());

        $config = new Config([
            'url' => 'https://flarum.localhost',
            'offline' => true,
        ]);

        $this->assertTrue($config->inHighMaintenanceMode());

        $config = new Config([
            'url' => 'https://flarum.localhost',
            'offline' => MaintenanceMode::LOW,
        ]);

        $this->assertFalse($config->inSafeMode());
        $this->assertTrue($config->inLowMaintenanceMode());
        $this->assertFalse($config->inHighMaintenanceMode());

        $config = new Config([
            'url' => 'https://flarum.localhost',
            'offline' => MaintenanceMode::SAFE,
        ]);

        $this->assertTrue($config->inSafeMode());
        $this->assertFalse($config->inLowMaintenanceMode());
        $this->assertFalse($config->inHighMaintenanceMode());
    }

    #[Test]
    public function it_turns_off_maintenance_mode_by_default()
    {
        $config = new Config([
            'url' => 'https://flarum.localhost',
        ]);

        $this->assertFalse($config->inHighMaintenanceMode());
    }

    #[Test]
    public function it_exposes_additional_keys_via_array_access()
    {
        $config = new Config([
            'url' => 'https://flarum.localhost',
            'custom_a' => 'b',
        ]);

        $this->assertEquals('b', $config['custom_a']);
    }

    #[Test]
    public function it_exposes_nested_keys_via_dot_syntax()
    {
        $config = new Config([
            'url' => 'https://flarum.localhost',
            'nested' => [
                'first' => '1',
                'second' => '2',
            ],
        ]);

        $this->assertEquals('1', $config['nested.first']);
        $this->assertEquals('2', $config['nested.second']);
    }

    #[Test]
    public function it_does_not_allow_mutation_via_array_access()
    {
        $config = new Config([
            'url' => 'https://flarum.localhost',
            'custom_a' => 'b',
        ]);

        try {
            $config['custom_a'] = 'c';
        } catch (RuntimeException) {
        }

        // Ensure the value was not changed
        $this->assertEquals('b', $config['custom_a']);
    }

    #[Test]
    public function it_does_not_allow_removal_via_array_access()
    {
        $config = new Config([
            'url' => 'https://flarum.localhost',
            'custom_a' => 'b',
        ]);

        try {
            unset($config['custom_a']);
        } catch (RuntimeException) {
        }

        // Ensure the value was not changed
        $this->assertEquals('b', $config['custom_a']);
    }

    #[Test]
    public function it_returns_queue_driver_from_config()
    {
        $config = new Config([
            'url' => 'https://flarum.localhost',
            'queue' => [
                'driver' => 'database',
            ],
        ]);

        $this->assertEquals('database', $config->queueDriver());
    }

    #[Test]
    public function it_returns_null_for_missing_queue_driver()
    {
        $config = new Config([
            'url' => 'https://flarum.localhost',
        ]);

        $this->assertNull($config->queueDriver());
    }

    #[Test]
    public function it_returns_null_for_empty_queue_config()
    {
        $config = new Config([
            'url' => 'https://flarum.localhost',
            'queue' => [],
        ]);

        $this->assertNull($config->queueDriver());
    }

    #[Test]
    public function it_returns_null_for_missing_fontawesome_config()
    {
        $config = new Config([
            'url' => 'https://flarum.localhost',
        ]);

        $this->assertNull($config->fontawesomeSource());
        $this->assertNull($config->fontawesomeCdnUrl());
        $this->assertNull($config->fontawesomeKitUrl());
    }

    #[Test]
    public function it_returns_fontawesome_source_from_config()
    {
        $config = new Config([
            'url' => 'https://flarum.localhost',
            'fontawesome' => [
                'source' => 'cdn',
            ],
        ]);

        $this->assertEquals('cdn', $config->fontawesomeSource());
    }

    #[Test]
    public function it_returns_fontawesome_cdn_url_from_config()
    {
        $config = new Config([
            'url' => 'https://flarum.localhost',
            'fontawesome' => [
                'cdn_url' => 'https://cdn.example.com/fontawesome.css',
            ],
        ]);

        $this->assertEquals('https://cdn.example.com/fontawesome.css', $config->fontawesomeCdnUrl());
    }

    #[Test]
    public function it_returns_fontawesome_kit_url_from_config()
    {
        $config = new Config([
            'url' => 'https://flarum.localhost',
            'fontawesome' => [
                'kit_url' => 'https://kit.fontawesome.com/abc123.js',
            ],
        ]);

        $this->assertEquals('https://kit.fontawesome.com/abc123.js', $config->fontawesomeKitUrl());
    }
}
