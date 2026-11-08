<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Forum\Branding;

use Flarum\Forum\Branding\StoredFiles;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\unit\TestCase;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Filesystem\Factory;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;

class StoredFilesTest extends TestCase
{
    #[Test]
    public function files_are_stored_with_their_content_type_and_cached_for_good(): void
    {
        $settings = m::mock(SettingsRepositoryInterface::class);
        $settings->shouldReceive('get')->andReturn(null);
        $settings->shouldReceive('set');

        $stored = [];
        $disk = m::mock(Cloud::class);
        $disk->shouldReceive('put')->andReturnUsing(function (string $path, string $contents, array $options = []) use (&$stored) {
            $stored[pathinfo($path, PATHINFO_EXTENSION)] = $options;

            return true;
        });

        $factory = m::mock(Factory::class);
        $factory->shouldReceive('disk')->with('flarum-assets')->andReturn($disk);

        (new StoredFiles($settings, $factory))->replaceSet('icons', [
            'icon.png' => 'png',
            'favicon.ico' => 'ico',
            'favicon.svg' => 'svg',
        ]);

        $this->assertSame('image/png', $stored['png']['mimetype'] ?? null);
        $this->assertSame('image/x-icon', $stored['ico']['mimetype'] ?? null);
        $this->assertSame('image/svg+xml', $stored['svg']['mimetype'] ?? null);
        $this->assertSame('public, max-age=31536000, immutable', $stored['svg']['CacheControl'] ?? null);
    }
}
