<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\tests\integration\api;

use Carbon\Carbon;
use Flarum\Gdpr\Console\ProcessEraseRequests;
use Flarum\Gdpr\Models\ErasureRequest;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Erasure requests whose user was deleted by other means, e.g. by an admin.
 */
class OrphanedErasureRequestTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-gdpr');

        $this->setting('mail_driver', 'log');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'moderator', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'moderator@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'user4', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'user4@machine.local', 'is_email_confirmed' => 1],
                ['id' => 5, 'username' => 'user5', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'user5@machine.local', 'is_email_confirmed' => 1],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 4],
            ],
            'group_permission' => [
                ['permission' => 'processErasure', 'group_id' => 4],
            ],
            'gdpr_erasure' => [
                ['id' => 1, 'user_id' => 4, 'verification_token' => 'abc123', 'status' => 'user_confirmed', 'created_at' => Carbon::now(), 'user_confirmed_at' => Carbon::now()],
                ['id' => 2, 'user_id' => 5, 'verification_token' => '123abc', 'status' => 'user_confirmed', 'created_at' => Carbon::now(), 'user_confirmed_at' => Carbon::now()],
                // What the `set null` foreign key leaves behind once the user is deleted.
                ['id' => 3, 'user_id' => null, 'verification_token' => 'zyx321', 'status' => 'user_confirmed', 'created_at' => Carbon::now(), 'user_confirmed_at' => Carbon::now()],
            ],
        ]);
    }

    #[Test]
    public function requests_without_a_user_are_not_listed()
    {
        $this->assertEquals([1, 2], $this->listedRequestIds());
    }

    #[Test]
    public function requests_without_a_user_are_not_counted()
    {
        $this->assertEquals(2, $this->erasureRequestCount());
    }

    #[Test]
    public function deleting_a_user_outside_gdpr_removes_their_request_from_the_list_and_count()
    {
        $this->app();

        // Leave out the orphan seeded in setUp(), so only the deletion below can orphan a request.
        ErasureRequest::query()->whereKey(3)->delete();
        $this->assertEquals([1, 2], $this->listedRequestIds());
        $this->assertEquals(2, $this->erasureRequestCount());

        // Not through `DELETE /api/users/{id}`: with this extension enabled that endpoint
        // processes the request itself. This is how another extension or a console
        // command would delete the user.
        User::query()->findOrFail(4)->delete();

        // The request is orphaned: still confirmed, with no user. Depending on whether
        // the database enforces the `set null` foreign key, `user_id` is null or dangling.
        $request = ErasureRequest::query()->findOrFail(1);
        $this->assertEquals(ErasureRequest::STATUS_USER_CONFIRMED, $request->status);
        $this->assertNull($request->user);

        $this->assertEquals([2], $this->listedRequestIds());
        $this->assertEquals(1, $this->erasureRequestCount());
    }

    #[Test]
    public function scheduled_processing_skips_the_erasure_of_a_deleted_user()
    {
        $app = $this->app();

        ErasureRequest::query()->whereKey(3)->update(['user_confirmed_at' => Carbon::now()->subDays(31)]);

        // A queue worker turns PHP warnings into exceptions, so fail on them here too.
        set_error_handler(function (int $severity, string $message, string $file, int $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            $app->getContainer()->call([new ProcessEraseRequests(), 'handle']);
        } finally {
            restore_error_handler();
        }

        $this->assertEquals(ErasureRequest::STATUS_PROCESSED, ErasureRequest::query()->find(3)->status);
        $this->assertNotNull(User::query()->find(4));
        $this->assertNotNull(User::query()->find(5));
    }

    private function listedRequestIds(): array
    {
        $response = $this->send(
            $this->request('GET', '/api/user-erasure-requests', [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $ids = array_map('intval', array_column(json_decode($response->getBody()->getContents(), true)['data'], 'id'));
        sort($ids);

        return $ids;
    }

    private function erasureRequestCount(): int
    {
        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        return json_decode($response->getBody()->getContents(), true)['data']['attributes']['erasureRequestCount'];
    }
}
