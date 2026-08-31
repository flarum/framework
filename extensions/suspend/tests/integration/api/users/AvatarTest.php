<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Suspend\Tests\integration\api\users;

use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Laminas\Diactoros\Stream;
use Laminas\Diactoros\UploadedFile;
use PHPUnit\Framework\Attributes\Test;

class AvatarTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-suspend');

        $this->prepareDatabase([
            User::class => [
                ['id' => 1, 'username' => 'Muralf', 'email' => 'muralf@machine.local', 'is_email_confirmed' => 1, 'avatar_url' => null],
                [
                    'id' => 2,
                    'username' => 'SuspendedDonny',
                    'email' => 'acme@machine.local',
                    'is_email_confirmed' => 1,
                    'avatar_url' => null,
                    'suspended_until' => Carbon::now()->addDay(),
                    'suspend_reason' => 'acme',
                    'suspend_message' => 'acme',
                ],
                ['id' => 3, 'username' => 'NotSuspended', 'email' => 'fine@machine.local', 'is_email_confirmed' => 1, 'avatar_url' => null],
            ],
        ]);
    }

    protected function avatarFile(): UploadedFile
    {
        $path = __DIR__.'/../../../fixtures/avatar.png';

        return new UploadedFile(new Stream($path), filesize($path), UPLOAD_ERR_OK, 'avatar.png', 'image/png');
    }

    #[Test]
    public function suspended_user_cannot_upload_an_avatar(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/users/2/avatar', ['authenticatedAs' => 2])
                ->withUploadedFiles(['avatar' => $this->avatarFile()])
        );

        $this->assertEquals(403, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNull(User::find(2)->avatar_url);
    }

    #[Test]
    public function suspended_user_cannot_remove_their_avatar(): void
    {
        $this->database()->table('users')->where('id', 2)->update(['avatar_url' => 'seeded.png']);

        $response = $this->send(
            $this->request('DELETE', '/api/users/2/avatar', ['authenticatedAs' => 2])
        );

        $this->assertEquals(403, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals('seeded.png', User::find(2)->getRawOriginal('avatar_url'));
    }

    #[Test]
    public function unsuspended_user_can_still_upload_an_avatar(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/users/3/avatar', ['authenticatedAs' => 3])
                ->withUploadedFiles(['avatar' => $this->avatarFile()])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNotNull(User::find(3)->avatar_url);
    }
}
