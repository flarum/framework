<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Extension;

use Flarum\Extension\AbandonedExtensionsFetcher;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AbandonedExtensionsFetcherTest extends TestCase
{
    private function settingsWithMap(?string $json): SettingsRepositoryInterface
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')
            ->with(AbandonedExtensionsFetcher::SETTINGS_KEY)
            ->willReturn($json);

        return $settings;
    }

    #[Test]
    public function returns_empty_array_when_no_cached_map(): void
    {
        $map = AbandonedExtensionsFetcher::getCachedMap($this->settingsWithMap(null));

        $this->assertSame([], $map);
    }

    #[Test]
    public function returns_empty_array_when_cached_value_is_invalid_json(): void
    {
        $map = AbandonedExtensionsFetcher::getCachedMap($this->settingsWithMap('not-valid-json'));

        $this->assertSame([], $map);
    }

    #[Test]
    public function returns_abandoned_entry_without_replacement(): void
    {
        $map = AbandonedExtensionsFetcher::getCachedMap($this->settingsWithMap(json_encode([
            'vendor/old-package' => [],
        ])));

        $this->assertArrayHasKey('vendor/old-package', $map);
        $this->assertSame([], $map['vendor/old-package']);
    }

    #[Test]
    public function returns_abandoned_entry_with_replacement(): void
    {
        $map = AbandonedExtensionsFetcher::getCachedMap($this->settingsWithMap(json_encode([
            'vendor/old-package' => ['replacement' => 'vendor/new-package'],
        ])));

        $this->assertSame('vendor/new-package', $map['vendor/old-package']['replacement']);
    }

    #[Test]
    public function returns_multiple_entries(): void
    {
        $map = AbandonedExtensionsFetcher::getCachedMap($this->settingsWithMap(json_encode([
            'vendor/pkg-a' => ['replacement' => 'vendor/pkg-b'],
            'vendor/pkg-c' => [],
        ])));

        $this->assertCount(2, $map);
        $this->assertArrayHasKey('vendor/pkg-a', $map);
        $this->assertArrayHasKey('vendor/pkg-c', $map);
    }
}
