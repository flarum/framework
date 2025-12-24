<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\tests\integration\api;

use Flarum\Gdpr\Models\ErasureRequest;
use Flarum\Notification\Notification;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class RequestErasureTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();
        $this->extension('flarum-gdpr');

        $this->setting('mail_driver', 'log');
        $this->setting('forum_title', 'Flarum Test');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'moderator', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'moderator@machine.local', 'is_email_confirmed' => 1],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 4],
            ],
            'group_permission' => [
                ['permission' => 'processErasure', 'group_id' => 4],
            ],
        ]);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        Notification::query()->delete();
        ErasureRequest::query()->delete();
    }

    #[Test]
    public function guest_cannot_request_erasure()
    {
        $response = $this->send(
            $this->request(
                'POST',
                '/api/user-erasure-requests',
            )->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(401, $response->getStatusCode());
    }

    #[Test]
    public function user_providing_incorrect_password_cannot_request_erasure()
    {
        $response = $this->send(
            $this->request(
                'POST',
                '/api/user-erasure-requests',
                [
                    'authenticatedAs' => 2,
                    'json' => [
                        'data' => [
                            'attributes' => [
                                'reason' => 'I want to be forgotten',

                            ],
                            'meta' => [
                                'password' => 'wrong-password',
                            ],
                        ],
                    ],
                ]
            )->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(422, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals('validation_error', $body['errors'][0]['code']);
        $this->assertEquals('Incorrect password', $body['errors'][0]['detail']);
    }

    #[Test]
    public function normal_user_can_request_erasure_and_recieves_notification_to_confirm()
    {
        $response = $this->send(
            $this->request(
                'POST',
                '/api/user-erasure-requests',
                [
                    'authenticatedAs' => 2,
                    'json' => [
                        'data' => [
                            'attributes' => [
                                'reason' => 'I want to be forgotten',

                            ],
                        ],
                        'meta' => [
                            'password' => 'too-obscure',
                        ],
                    ],
                ]
            )->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(201, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals('user-erasure-requests', $body['data']['type']);

        $erasureId = $body['data']['id'];
        $this->assertNotNull($erasureId);

        $erasureRequest = ErasureRequest::query()->find($erasureId);

        $this->assertNotNull($erasureRequest);
        $this->assertIsString($erasureRequest->verification_token);
        $this->assertEquals('awaiting_user_confirmation', $erasureRequest->status);
        $this->assertEquals('I want to be forgotten', $erasureRequest->reason);
        $this->assertNotNull($erasureRequest->created_at);
        $this->assertNull($erasureRequest->user_confirmed_at);
        $this->assertNull($erasureRequest->processed_by);
        $this->assertNull($erasureRequest->processor_comment);
        $this->assertNull($erasureRequest->processed_at);
        $this->assertNull($erasureRequest->processed_mode);

        // TODO: Check that email was sent
    }

    #[Test]
    public function user_can_request_erasure_without_giving_reason()
    {
        $response = $this->send(
            $this->request(
                'POST',
                '/api/user-erasure-requests',
                [
                    'authenticatedAs' => 2,
                    'json' => [
                        'data' => [
                            'attributes' => [

                            ],
                        ],
                        'meta' => [
                            'password' => 'too-obscure',
                        ],
                    ],
                ]
            )->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(201, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals('user-erasure-requests', $body['data']['type']);

        $erasureId = $body['data']['id'];
        $this->assertNotNull($erasureId);

        $erasureRequest = ErasureRequest::query()->find($erasureId);

        $this->assertNotNull($erasureRequest);
        $this->assertIsString($erasureRequest->verification_token);
        $this->assertEquals('awaiting_user_confirmation', $erasureRequest->status);
        $this->assertNull($erasureRequest->reason);
        $this->assertNotNull($erasureRequest->created_at);
        $this->assertNull($erasureRequest->user_confirmed_at);
        $this->assertNull($erasureRequest->processed_by);
        $this->assertNull($erasureRequest->processor_comment);
        $this->assertNull($erasureRequest->processed_at);
        $this->assertNull($erasureRequest->processed_mode);

        // TODO: Check that email was sent
    }

    #[Test]
    public function moderator_with_permission_does_not_see_requests_pending_confirmation()
    {
        $this->normal_user_can_request_erasure_and_recieves_notification_to_confirm();

        $response = $this->send(
            $this->request(
                'GET',
                '/api/user-erasure-requests',
                [
                    'authenticatedAs' => 3,
                ]
            )->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertEmpty($body['data']);

        $erasureRequests = ErasureRequest::query()->where('user_id', 2)->get();

        $this->assertNotEmpty($erasureRequests);
    }
}
