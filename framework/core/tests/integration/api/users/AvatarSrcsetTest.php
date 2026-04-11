<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\users;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class AvatarSrcsetTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                [
                    'id' => 3,
                    'username' => 'local_avatar_user',
                    'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim',
                    'email' => 'localavatar@machine.local',
                    'is_email_confirmed' => 1,
                    'avatar_url' => 'ABCDEFGHabcdefgh.webp',
                ],
                [
                    'id' => 4,
                    'username' => 'external_avatar_user',
                    'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim',
                    'email' => 'externalavatar@machine.local',
                    'is_email_confirmed' => 1,
                    'avatar_url' => 'https://example.com/avatar.png',
                ],
            ],
        ]);
    }

    #[Test]
    public function user_without_avatar_has_null_avatar_srcset(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/users/2', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('avatarSrcset', $data['data']['attributes']);
        $this->assertNull($data['data']['attributes']['avatarSrcset']);
    }

    #[Test]
    public function user_with_external_url_avatar_has_null_avatar_srcset(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/users/4', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('avatarSrcset', $data['data']['attributes']);
        // External URLs never have locally stored HiDPI variants.
        $this->assertNull($data['data']['attributes']['avatarSrcset']);
    }

    #[Test]
    public function user_with_local_avatar_but_no_hidpi_variants_has_null_avatar_srcset(): void
    {
        // The flarum-avatars disk in integration tests is backed by local storage.
        // No physical files are written, so exists() returns false for all variants —
        // srcsetFor() returns null (only meaningful when HiDPI variants exist).
        $response = $this->send(
            $this->request('GET', '/api/users/3', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('avatarSrcset', $data['data']['attributes']);
        $this->assertNull($data['data']['attributes']['avatarSrcset']);
    }

    #[Test]
    public function deleting_avatar_returns_null_avatar_srcset(): void
    {
        $response = $this->send(
            $this->request('DELETE', '/api/users/2/avatar', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('avatarSrcset', $data['data']['attributes']);
        $this->assertNull($data['data']['attributes']['avatarSrcset']);
    }
}
