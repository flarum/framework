<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Tests\integration;

use Carbon\Carbon;
use Flarum\Audit\Tests\integration\InteractsWithAuditLog;
use Flarum\Gdpr\Console\ProcessEraseRequests;
use Flarum\Gdpr\Models\ErasureRequest;
use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Filesystem\Factory;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

class AuditTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use InteractsWithAuditLog;

    public function setUp(): void
    {
        parent::setUp();

        $this->setUpAuditLog();

        $this->extension('flarum-audit', 'flarum-gdpr');

        $this->setting('mail_driver', 'log');
        $this->setting('forum_title', 'Flarum Test');
        $this->setting('flarum-gdpr.allow-deletion', true);
        $this->setting('flarum-gdpr.allow-anonymization', true);

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                // id 3: a moderator who can process erasures and export others' data
                ['id' => 3, 'username' => 'moderator', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'moderator@machine.local', 'is_email_confirmed' => 1],
                // subjects of erasure/export
                ['id' => 4, 'username' => 'user4', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'user4@machine.local', 'is_email_confirmed' => 1],
                ['id' => 5, 'username' => 'user5', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'user5@machine.local', 'is_email_confirmed' => 1],
                ['id' => 6, 'username' => 'user6', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'user6@machine.local', 'is_email_confirmed' => 1],
            ],
            Group::class => [
                ['id' => 5, 'name_singular' => 'customgroup', 'name_plural' => 'customgroups'],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 4],
            ],
            'group_permission' => [
                ['permission' => 'processErasure', 'group_id' => 4],
                ['permission' => 'moderateExport', 'group_id' => 4],
            ],
            'gdpr_erasure' => [
                // confirmed, ready for an admin to process (used for deletion/anonymization)
                ['id' => 1, 'user_id' => 4, 'verification_token' => 'tok1', 'status' => 'user_confirmed', 'created_at' => Carbon::now(), 'user_confirmed_at' => Carbon::now()],
                ['id' => 2, 'user_id' => 5, 'verification_token' => 'tok2', 'status' => 'user_confirmed', 'created_at' => Carbon::now(), 'user_confirmed_at' => Carbon::now()],
                // confirmed long ago, with no processor — eligible for the scheduled (system) task
                ['id' => 3, 'user_id' => 6, 'verification_token' => 'tok3', 'status' => 'user_confirmed', 'created_at' => Carbon::now()->subDays(40), 'user_confirmed_at' => Carbon::now()->subDays(40)],
            ],
            'gdpr_exports' => [],
            'notifications' => [],
        ]);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $fs = $this->app()->getContainer()->make(Factory::class)->disk('gdpr-export');
        $fs->delete($fs->allFiles());
    }

    protected function process(int $id, string $mode): ResponseInterface
    {
        return $this->send(
            $this->request('PATCH', "/api/user-erasure-requests/$id", [
                'authenticatedAs' => 3,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'processorComment' => 'Processed in test',
                            'processedMode' => $mode,
                        ],
                    ],
                ],
            ])
        );
    }

    #[Test]
    public function admin_processed_deletion_is_logged_with_processor_as_actor()
    {
        $response = $this->process(1, ErasureRequest::MODE_DELETION);
        $this->assertEquals(200, $response->getStatusCode());

        // The subject (user 4) was deleted.
        $this->assertNull(User::find(4));

        // Logged as a deletion, attributed to the processing admin (user 3), with processed_by in the payload.
        $this->assertLogExists('user.gdpr_deleted', [
            'user_id' => 4,
            'processed_by' => 3,
        ], 3);
    }

    #[Test]
    public function admin_processed_anonymization_is_logged_with_processor_as_actor()
    {
        $response = $this->process(2, ErasureRequest::MODE_ANONYMIZATION);
        $this->assertEquals(200, $response->getStatusCode());

        // The subject (user 5) was anonymized, not deleted.
        $this->assertNotNull(User::find(5));

        $this->assertLogExists('user.gdpr_anonymized', [
            'user_id' => 5,
            'processed_by' => 3,
        ], 3);
    }

    #[Test]
    public function system_processed_erasure_is_logged_with_no_actor()
    {
        $this->setting('flarum-gdpr.default-erasure', ErasureRequest::MODE_DELETION);

        // Drive the scheduled task that auto-processes long-confirmed requests with no admin.
        $this->app()->getContainer()->call([new ProcessEraseRequests(), 'handle']);

        $this->assertNull(User::find(6));

        // Logged with NO actor (system erasure): actor_id null, processed_by null in the payload,
        // and no IP since it ran from the scheduled task rather than an HTTP request.
        $this->assertLogExists('user.gdpr_deleted', [
            'user_id' => 6,
            'processed_by' => null,
        ], null, 0, null);
    }

    #[Test]
    public function export_is_logged_with_requesting_actor()
    {
        $response = $this->send(
            $this->request('POST', '/api/gdpr-exports', [
                'authenticatedAs' => 3,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'userId' => 4,
                        ],
                    ],
                ],
            ])->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(201, $response->getStatusCode());

        // Attributed to the actor who requested the export (moderator, user 3).
        $this->assertLogExists('user.gdpr_exported', [
            'user_id' => 4,
        ], 3);
    }
}
