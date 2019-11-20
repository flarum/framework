<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\User;

use Flarum\User\AvatarUploader;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Intervention\Image\ImageManagerStatic;
use League\Flysystem\FilesystemInterface;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class AvatarUploaderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $dispatcher;
    private $filesystem;
    private $uploader;

    public function setUp()
    {
        $this->dispatcher = m::mock(Dispatcher::class);
        $this->dispatcher->shouldIgnoreMissing();
        Model::setEventDispatcher($this->dispatcher);

        $this->filesystem = m::mock(FilesystemInterface::class);
        $this->uploader = new AvatarUploader($this->filesystem);
    }

    public function test_removing_avatar_removes_file()
    {
        $this->filesystem->shouldReceive('has')->with('ABCDEFGHabcdefgh.png');
        $this->filesystem->shouldReceive('delete')->with('ABCDEFGHabcdefgh.png')->andReturn(true);

        $user = new User();
        $user->changeAvatarPath('ABCDEFGHabcdefgh.png');
        // Necessary because AvatarUpload looks into the original attributes for the raw value,
        // which isn't usually automatically updated without saving to the database
        $user->syncOriginal();

        $this->uploader->remove($user);
        $user->syncOriginal();

        $this->assertEquals(null, $user->getOriginal('avatar_url'));
    }

    public function test_removing_url_avatar_removes_no_file()
    {
        $this->filesystem->shouldReceive('has')->with('https://example.com/avatar.png')->andReturn(false);
        $this->filesystem->shouldNotReceive('delete');

        $user = new User();
        $user->changeAvatarPath('https://example.com/avatar.png');
        $user->syncOriginal();

        $this->uploader->remove($user);
        $user->syncOriginal();

        $this->assertEquals(null, $user->getOriginal('avatar_url'));
    }

    public function test_changing_avatar_removes_file()
    {
        $this->filesystem->shouldReceive('put');
        $this->filesystem->shouldReceive('has')->with('ABCDEFGHabcdefgh.png')->andReturn(true);
        $this->filesystem->shouldReceive('delete')->with('ABCDEFGHabcdefgh.png');

        $user = new User();
        $user->changeAvatarPath('ABCDEFGHabcdefgh.png');
        $user->syncOriginal();

        $this->uploader->upload($user, ImageManagerStatic::canvas(50, 50));
        $user->syncOriginal();

        $this->assertNotEquals('ABCDEFGHabcdefgh.png', $user->getOriginal('avatar_url'));
    }
}
