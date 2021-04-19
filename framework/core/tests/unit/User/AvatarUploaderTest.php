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
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Intervention\Image\ImageManagerStatic;
use Mockery as m;

class AvatarUploaderTest extends TestCase
{
    private $dispatcher;
    private $filesystem;
    private $filesystemFactory;
    private $uploader;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->dispatcher = m::mock(Dispatcher::class);
        $this->dispatcher->shouldIgnoreMissing();
        Model::setEventDispatcher($this->dispatcher);

        $this->filesystem = m::mock(Filesystem::class);
        $this->filesystemFactory = m::mock(Factory::class);
        $this->filesystemFactory->shouldReceive('disk')->with('flarum-avatars')->andReturn($this->filesystem);
        $this->uploader = new AvatarUploader($this->filesystemFactory);
    }

    public function test_removing_avatar_removes_file()
    {
        $this->filesystem->shouldReceive('exists')->with('ABCDEFGHabcdefgh.png')->andReturn(true);
        $this->filesystem->shouldReceive('delete')->with('ABCDEFGHabcdefgh.png')->once();

        $user = new User();
        $user->changeAvatarPath('ABCDEFGHabcdefgh.png');
        // Necessary because AvatarUpload looks into the original attributes for the raw value,
        // which isn't usually automatically updated without saving to the database
        $user->syncOriginal();

        $this->uploader->remove($user);

        // Simulate saving
        foreach ($user->releaseAfterSaveCallbacks() as $callback) {
            $callback($user);
        }
        $user->syncOriginal();

        $this->assertEquals(null, $user->getRawOriginal('avatar_url'));
    }

    public function test_removing_url_avatar_removes_no_file()
    {
        $this->filesystem->shouldReceive('exists')->with('https://example.com/avatar.png')->andReturn(false)->once();
        $this->filesystem->shouldNotReceive('delete');

        $user = new User();
        $user->changeAvatarPath('https://example.com/avatar.png');
        $user->syncOriginal();

        $this->uploader->remove($user);

        // Simulate saving
        foreach ($user->releaseAfterSaveCallbacks() as $callback) {
            $callback($user);
        }
        $user->syncOriginal();

        $this->assertEquals(null, $user->getRawOriginal('avatar_url'));
    }

    public function test_changing_avatar_removes_file()
    {
        $this->filesystem->shouldReceive('put')->once();
        $this->filesystem->shouldReceive('exists')->with('ABCDEFGHabcdefgh.png')->andReturn(true);
        $this->filesystem->shouldReceive('delete')->with('ABCDEFGHabcdefgh.png')->once();

        $user = new User();
        $user->changeAvatarPath('ABCDEFGHabcdefgh.png');
        $user->syncOriginal();

        $this->uploader->upload($user, ImageManagerStatic::canvas(50, 50));

        // Simulate saving
        foreach ($user->releaseAfterSaveCallbacks() as $callback) {
            $callback($user);
        }
        $user->syncOriginal();

        $this->assertNotEquals('ABCDEFGHabcdefgh.png', $user->getRawOriginal('avatar_url'));
    }
}
