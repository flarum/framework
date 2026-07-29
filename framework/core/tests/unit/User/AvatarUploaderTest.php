<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\User;

use Flarum\Testing\unit\TestCase;
use Flarum\User\AvatarUploader;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Database\Eloquent\Model;
use Intervention\Image\ImageManager;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;

class AvatarUploaderTest extends TestCase
{
    private $dispatcher;
    private $filesystem;
    private $filesystemFactory;
    private AvatarUploader $uploader;

    protected function setUp(): void
    {
        $this->dispatcher = m::mock(Dispatcher::class);
        $this->dispatcher->shouldIgnoreMissing();
        Model::setEventDispatcher($this->dispatcher);

        $this->filesystem = m::mock(Cloud::class);
        $this->filesystemFactory = m::mock(Factory::class);
        $this->filesystemFactory->shouldReceive('disk')->with('flarum-avatars')->andReturn($this->filesystem);
        $this->uploader = new AvatarUploader($this->filesystemFactory);
    }

    #[Test]
    public function test_removing_avatar_removes_file()
    {
        $this->filesystem->shouldReceive('exists')->with('ABCDEFGHabcdefgh.png')->andReturn(true);
        $this->filesystem->shouldReceive('delete')->with('ABCDEFGHabcdefgh.png')->once();
        // @2x and @3x variants — don't exist
        $this->filesystem->shouldReceive('exists')->with('ABCDEFGHabcdefgh@2x.png')->andReturn(false);
        $this->filesystem->shouldReceive('exists')->with('ABCDEFGHabcdefgh@3x.png')->andReturn(false);

        $user = new User();
        $user->changeAvatarPath('ABCDEFGHabcdefgh.png');
        $user->has_avatar_2x = true;
        $user->has_avatar_3x = true;
        $user->syncOriginal();

        $this->uploader->remove($user);

        foreach ($user->releaseAfterSaveCallbacks() as $callback) {
            $callback($user);
        }
        $user->syncOriginal();

        $this->assertEquals(null, $user->getRawOriginal('avatar_url'));
        $this->assertFalse($user->has_avatar_2x);
        $this->assertFalse($user->has_avatar_3x);
    }

    #[Test]
    public function test_removing_url_avatar_removes_no_file()
    {
        $this->filesystem->shouldReceive('exists')->andReturn(false);
        $this->filesystem->shouldNotReceive('delete');

        $user = new User();
        $user->changeAvatarPath('https://example.com/avatar.png');
        $user->syncOriginal();

        $this->uploader->remove($user);

        foreach ($user->releaseAfterSaveCallbacks() as $callback) {
            $callback($user);
        }
        $user->syncOriginal();

        $this->assertEquals(null, $user->getRawOriginal('avatar_url'));
    }

    #[Test]
    public function test_changing_avatar_removes_old_file_and_all_variants()
    {
        // Old base + variants exist
        $this->filesystem->shouldReceive('exists')->with('ABCDEFGHabcdefgh.png')->andReturn(true);
        $this->filesystem->shouldReceive('exists')->with('ABCDEFGHabcdefgh@2x.png')->andReturn(true);
        $this->filesystem->shouldReceive('exists')->with('ABCDEFGHabcdefgh@3x.png')->andReturn(true);
        $this->filesystem->shouldReceive('delete')->with('ABCDEFGHabcdefgh.png')->once();
        $this->filesystem->shouldReceive('delete')->with('ABCDEFGHabcdefgh@2x.png')->once();
        $this->filesystem->shouldReceive('delete')->with('ABCDEFGHabcdefgh@3x.png')->once();
        // New files being stored
        $this->filesystem->shouldReceive('put')->times(3); // 1x, 2x, 3x from a 300px source

        $user = new User();
        $user->changeAvatarPath('ABCDEFGHabcdefgh.png');
        $user->syncOriginal();

        // 300px source — all three variants should be generated
        $this->uploader->upload($user, ImageManager::gd()->create(300, 300));

        foreach ($user->releaseAfterSaveCallbacks() as $callback) {
            $callback($user);
        }
        $user->syncOriginal();

        $this->assertNotEquals('ABCDEFGHabcdefgh.png', $user->getRawOriginal('avatar_url'));
    }

    #[Test]
    public function test_upload_stores_webp_for_static_images()
    {
        $this->filesystem->shouldReceive('put')->atLeast()->once();
        $this->filesystem->shouldIgnoreMissing();

        $user = new User();

        $this->uploader->upload($user, ImageManager::gd()->create(300, 300));

        foreach ($user->releaseAfterSaveCallbacks() as $callback) {
            $callback($user);
        }
        $user->syncOriginal();

        $this->assertStringEndsWith('.webp', $user->getRawOriginal('avatar_url'));
    }

    #[Test]
    public function test_upload_generates_all_three_variants_for_large_source()
    {
        $putPaths = [];
        $this->filesystem->shouldReceive('put')->andReturnUsing(function (string $path) use (&$putPaths) {
            $putPaths[] = $path;
        });
        $this->filesystem->shouldIgnoreMissing();

        $user = new User();

        // 300px source — should generate 1x, 2x, and 3x
        $this->uploader->upload($user, ImageManager::gd()->create(300, 300));

        $this->assertCount(3, $putPaths);
        $this->assertStringNotContainsString('@', $putPaths[0]);
        $this->assertStringContainsString('@2x', $putPaths[1]);
        $this->assertStringContainsString('@3x', $putPaths[2]);
        $this->assertTrue($user->has_avatar_2x);
        $this->assertTrue($user->has_avatar_3x);
    }

    #[Test]
    public function test_upload_skips_variants_that_would_require_upscaling()
    {
        $putPaths = [];
        $this->filesystem->shouldReceive('put')->andReturnUsing(function (string $path) use (&$putPaths) {
            $putPaths[] = $path;
        });
        $this->filesystem->shouldIgnoreMissing();

        $user = new User();

        // 150px source — should only generate 1x (100px). 2x (200px) and 3x (300px) would upscale.
        $this->uploader->upload($user, ImageManager::gd()->create(150, 150));

        $this->assertCount(1, $putPaths);
        $this->assertStringNotContainsString('@', $putPaths[0]);
        $this->assertFalse($user->has_avatar_2x);
        $this->assertFalse($user->has_avatar_3x);
    }

    #[Test]
    public function test_upload_generates_two_variants_for_mid_size_source()
    {
        $putPaths = [];
        $this->filesystem->shouldReceive('put')->andReturnUsing(function (string $path) use (&$putPaths) {
            $putPaths[] = $path;
        });
        $this->filesystem->shouldIgnoreMissing();

        $user = new User();

        // 200px source — should generate 1x and 2x, but not 3x (would upscale).
        $this->uploader->upload($user, ImageManager::gd()->create(200, 200));

        $this->assertCount(2, $putPaths);
        $this->assertStringNotContainsString('@', $putPaths[0]);
        $this->assertStringContainsString('@2x', $putPaths[1]);
        $this->assertTrue($user->has_avatar_2x);
        $this->assertFalse($user->has_avatar_3x);
    }

    #[Test]
    public function test_upload_generates_base_variant_for_small_source()
    {
        $putPaths = [];
        $this->filesystem->shouldReceive('put')->andReturnUsing(function (string $path) use (&$putPaths) {
            $putPaths[] = $path;
        });
        $this->filesystem->shouldIgnoreMissing();

        $user = new User();

        // 50px source — should generate only the base variant, even though it would upscale, since we always want a 1x.
        $this->uploader->upload($user, ImageManager::gd()->create(50, 50));

        $this->assertCount(1, $putPaths);
        $this->assertStringNotContainsString('@', $putPaths[0]);
        $this->assertFalse($user->has_avatar_2x);
        $this->assertFalse($user->has_avatar_3x);
    }

    #[Test]
    public function test_upload_presized_records_only_supplied_variants()
    {
        $this->filesystem->shouldReceive('put')->atLeast()->once();
        $this->filesystem->shouldIgnoreMissing();

        $user = new User();

        // OAuth path with 1× and 2× only — no 3×.
        $this->uploader->uploadPresized(
            $user,
            ImageManager::gd()->create(100, 100),
            ImageManager::gd()->create(200, 200),
            null,
        );

        $this->assertTrue($user->has_avatar_2x);
        $this->assertFalse($user->has_avatar_3x);
    }

    #[Test]
    public function test_srcset_for_returns_null_when_no_variants_recorded()
    {
        // No filesystem calls should happen on the read path.
        $this->filesystem->shouldNotReceive('exists');
        $this->filesystem->shouldNotReceive('url');

        $user = new User();
        $user->setRawAttributes([
            'avatar_url' => 'abc.webp',
            'has_avatar_2x' => false,
            'has_avatar_3x' => false,
        ], true);

        $this->assertNull($this->uploader->srcsetFor($user));
    }

    #[Test]
    public function test_srcset_for_returns_string_when_hidpi_variants_recorded()
    {
        $this->filesystem->shouldNotReceive('exists');
        $this->filesystem->shouldReceive('url')->with('abc.webp')->andReturn('https://cdn.example.com/abc.webp');
        $this->filesystem->shouldReceive('url')->with('abc@2x.webp')->andReturn('https://cdn.example.com/abc@2x.webp');
        $this->filesystem->shouldReceive('url')->with('abc@3x.webp')->andReturn('https://cdn.example.com/abc@3x.webp');

        $user = new User();
        $user->setRawAttributes([
            'avatar_url' => 'abc.webp',
            'has_avatar_2x' => true,
            'has_avatar_3x' => true,
        ], true);

        $result = $this->uploader->srcsetFor($user);

        $this->assertNotNull($result);
        $this->assertStringContainsString('1x', $result);
        $this->assertStringContainsString('2x', $result);
        $this->assertStringContainsString('3x', $result);
    }

    #[Test]
    public function test_srcset_for_includes_only_recorded_variants()
    {
        $this->filesystem->shouldNotReceive('exists');
        $this->filesystem->shouldReceive('url')->with('abc.webp')->andReturn('https://cdn.example.com/abc.webp');
        $this->filesystem->shouldReceive('url')->with('abc@2x.webp')->andReturn('https://cdn.example.com/abc@2x.webp');
        $this->filesystem->shouldNotReceive('url')->with('abc@3x.webp');

        $user = new User();
        $user->setRawAttributes([
            'avatar_url' => 'abc.webp',
            'has_avatar_2x' => true,
            'has_avatar_3x' => false,
        ], true);

        $result = $this->uploader->srcsetFor($user);

        $this->assertNotNull($result);
        $this->assertStringContainsString('1x', $result);
        $this->assertStringContainsString('2x', $result);
        $this->assertStringNotContainsString('3x', $result);
    }

    #[Test]
    public function test_srcset_for_returns_null_for_external_url()
    {
        $this->filesystem->shouldNotReceive('exists');
        $this->filesystem->shouldNotReceive('url');

        $user = new User();
        $user->setRawAttributes(['avatar_url' => 'https://example.com/avatar.png'], true);

        $this->assertNull($this->uploader->srcsetFor($user));
    }

    #[Test]
    public function test_srcset_for_returns_null_when_no_avatar()
    {
        $this->filesystem->shouldNotReceive('exists');
        $this->filesystem->shouldNotReceive('url');

        $user = new User();
        $user->setRawAttributes(['avatar_url' => null], true);

        $this->assertNull($this->uploader->srcsetFor($user));
    }

    #[Test]
    public function test_delete_all_variants_removes_all_existing_files()
    {
        $this->filesystem->shouldReceive('exists')->with('abc.webp')->andReturn(true);
        $this->filesystem->shouldReceive('exists')->with('abc@2x.webp')->andReturn(true);
        $this->filesystem->shouldReceive('exists')->with('abc@3x.webp')->andReturn(false);
        $this->filesystem->shouldReceive('delete')->with('abc.webp')->once();
        $this->filesystem->shouldReceive('delete')->with('abc@2x.webp')->once();
        $this->filesystem->shouldNotReceive('delete')->with('abc@3x.webp');

        $this->uploader->deleteAllVariants('abc.webp');
    }
}
