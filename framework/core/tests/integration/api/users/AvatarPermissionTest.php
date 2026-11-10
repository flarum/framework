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
use Laminas\Diactoros\Stream;
use Laminas\Diactoros\UploadedFile;
use PHPUnit\Framework\Attributes\Test;

class AvatarPermissionTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        // `avatar_url` is set explicitly: User rows go through the model factory, which
        // otherwise fills it with a faker placeholder and makes the assertions vacuous.
        $this->prepareDatabase([
            User::class => [
                $this->normalUser() + ['avatar_url' => null],
                [
                    'id' => 3,
                    'username' => 'someoneelse',
                    'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim',
                    'email' => 'someoneelse@machine.local',
                    'is_email_confirmed' => 1,
                    'avatar_url' => null,
                ],
            ],
        ]);
    }

    protected function revokeEditAvatar(): void
    {
        $this->database()->table('group_permission')
            ->where('permission', 'user.editAvatar')
            ->delete();
    }

    protected function avatarFile(): UploadedFile
    {
        $path = __DIR__.'/../../../fixtures/assets/avatar.png';

        return new UploadedFile(new Stream($path), filesize($path), UPLOAD_ERR_OK, 'avatar.png', 'image/png');
    }

    protected function upload(int $targetId, int $actorId)
    {
        return $this->send(
            $this->request('POST', "/api/users/$targetId/avatar", ['authenticatedAs' => $actorId])
                ->withUploadedFiles(['avatar' => $this->avatarFile()])
        );
    }

    protected function delete(int $targetId, int $actorId)
    {
        return $this->send(
            $this->request('DELETE', "/api/users/$targetId/avatar", ['authenticatedAs' => $actorId])
        );
    }

    #[Test]
    public function member_with_permission_can_upload_their_own_avatar(): void
    {
        $response = $this->upload(2, 2);

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNotNull(User::find(2)->avatar_url);
    }

    #[Test]
    public function member_with_permission_can_remove_their_own_avatar(): void
    {
        $this->upload(2, 2);

        $response = $this->delete(2, 2);

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNull(User::find(2)->avatar_url);
    }

    #[Test]
    public function member_without_permission_cannot_upload_their_own_avatar(): void
    {
        $this->revokeEditAvatar();

        $response = $this->upload(2, 2);

        $this->assertEquals(403, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNull(User::find(2)->avatar_url);
    }

    #[Test]
    public function member_without_permission_cannot_remove_their_own_avatar(): void
    {
        // The avatar is seeded rather than uploaded first: an earlier request would warm
        // the per-group-set permission cache before the revoke, and the delete would then
        // be authorised against a stale set.
        $this->database()->table('users')->where('id', 2)->update(['avatar_url' => 'seeded.png']);

        $this->revokeEditAvatar();

        $response = $this->delete(2, 2);

        $this->assertEquals(403, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNotNull(User::find(2)->avatar_url);
    }

    #[Test]
    public function member_cannot_upload_someone_elses_avatar(): void
    {
        $response = $this->upload(3, 2);

        $this->assertEquals(403, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNull(User::find(3)->avatar_url);
    }

    #[Test]
    public function admin_can_upload_someone_elses_avatar(): void
    {
        $response = $this->upload(3, 1);

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNotNull(User::find(3)->avatar_url);
    }

    /**
     * Moderators act on other users through `user.edit`, not `user.editAvatar` —
     * revoking the latter must not take away their moderation reach.
     */
    #[Test]
    public function user_with_edit_permission_can_upload_someone_elses_avatar_without_edit_avatar(): void
    {
        // Seeded before any database access, since prepareDatabase() is applied lazily
        // when the database is first populated.
        $this->prepareDatabase([
            'group_permission' => [
                ['permission' => 'user.edit', 'group_id' => 3],
            ],
        ]);

        $this->revokeEditAvatar();

        $response = $this->upload(3, 2);

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNotNull(User::find(3)->avatar_url);
    }

    /**
     * The delete side of the same rule: moderation reach over another user's avatar comes
     * from `user.edit`, and must survive `user.editAvatar` being revoked.
     */
    #[Test]
    public function user_with_edit_permission_can_remove_someone_elses_avatar_without_edit_avatar(): void
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['permission' => 'user.edit', 'group_id' => 3],
            ],
        ]);

        $this->database()->table('users')->where('id', 3)->update(['avatar_url' => 'seeded.png']);

        $this->revokeEditAvatar();

        $response = $this->delete(3, 2);

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNull(User::find(3)->avatar_url);
    }

    #[Test]
    public function member_cannot_remove_someone_elses_avatar(): void
    {
        $this->database()->table('users')->where('id', 3)->update(['avatar_url' => 'seeded.png']);

        $response = $this->delete(3, 2);

        $this->assertEquals(403, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals('seeded.png', User::find(3)->getRawOriginal('avatar_url'));
    }

    #[Test]
    public function guest_cannot_upload_an_avatar(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/users/2/avatar')
                ->withAttribute('bypassCsrfToken', true)
                ->withUploadedFiles(['avatar' => $this->avatarFile()])
        );

        $this->assertEquals(401, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNull(User::find(2)->avatar_url);
    }
}
