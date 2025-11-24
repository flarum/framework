<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\tests\integration\api;

use Carbon\Carbon;
use Flarum\Gdpr\Models\ErasureRequest;
use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class ProcessErasureTest extends TestCase
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
                ['id' => 5, 'username' => 'user5', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'user5@machine.local', 'is_email_confirmed' => 1, 'joined_at' => Carbon::now(), 'last_seen_at' => Carbon::now(), 'avatar_url' => 'avatar.jpg'],
                ['id' => 6, 'username' => 'user6', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'user6@machine.local', 'is_email_confirmed' => 1, 'joined_at' => Carbon::now(), 'last_seen_at' => Carbon::now(), 'avatar_url' => 'avatar.jpg'],
            ],
            Group::class => [
                ['id' => 5, 'name_singular' => 'customgroup', 'name_plural' => 'customgroups'],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 4],
                ['user_id' => 5, 'group_id' => 5],
            ],
            'group_permission' => [
                ['permission' => 'processErasure', 'group_id' => 4],
            ],
            'gdpr_erasure' => [
                ['id' => 1, 'user_id' => 4, 'verification_token' => 'abc123', 'status' => 'awaiting_user_confirmation', 'reason' => 'I want to be forgotten', 'created_at' => Carbon::now()],
                ['id' => 2, 'user_id' => 5, 'verification_token' => '123abc', 'status' => 'user_confirmed', 'reason' => 'I also want to be forgotten', 'created_at' => Carbon::now(), 'user_confirmed_at' => Carbon::now()],
                ['id' => 3, 'user_id' => 6, 'verification_token' => '321zyx', 'status' => 'cancelled', 'created_at' => Carbon::now()->subDay(), 'cancelled_at' => Carbon::now()],
            ],
        ]);
    }

    #[Test]
    public function unauthorized_users_cannot_see_erasure_requests()
    {
        $response = $this->send(
            $this->request('GET', '/api/user-erasure-requests', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function authorized_users_can_see_confirmed_erasure_requests()
    {
        $response = $this->send(
            $this->request('GET', '/api/user-erasure-requests', [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);

        $this->assertCount(1, $json['data']);
        $this->assertEquals(ErasureRequest::STATUS_USER_CONFIRMED, $json['data'][0]['attributes']['status']);
    }

    #[Test]
    public function unauthorized_user_cannot_process_erasure_request()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/user-erasure-requests/2', [
                'authenticatedAs' => 2,
                'json'            => [
                    'data' => [
                        'attributes' => [
                            'processor_comment' => 'I am trying to process this request',
                            'meta'              => [
                                'mode' => ErasureRequest::MODE_DELETION,
                            ],
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function authorized_user_cannot_process_unconfirmed_request()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/user-erasure-requests/1', [
                'authenticatedAs' => 3,
                'json'            => [
                    'data' => [
                        'attributes' => [
                            'processorComment' => 'I am trying to process this request',
                            'processedMode'    => ErasureRequest::MODE_DELETION,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function authorized_user_can_process_confirmed_erasure_request_in_deletion_mode()
    {
        $this->setting('flarum-gdpr.allow-deletion', true);

        $response = $this->send(
            $this->request('PATCH', '/api/user-erasure-requests/2', [
                'authenticatedAs' => 3,
                'json'            => [
                    'data' => [
                        'attributes' => [
                            'processorComment' => 'I have processed this request',
                            'processedMode'    => ErasureRequest::MODE_DELETION,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(ErasureRequest::STATUS_PROCESSED, $json['data']['attributes']['status']);
        $this->assertEquals(ErasureRequest::MODE_DELETION, $json['data']['attributes']['processedMode']);
        $this->assertEquals('I have processed this request', $json['data']['attributes']['processorComment']);

        $this->assertNull(User::find(5));
    }

    #[Test]
    public function authorized_user_can_process_confirmed_erasure_request_in_anonymization_mode()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/user-erasure-requests/2', [
                'authenticatedAs' => 3,
                'json'            => [
                    'data' => [
                        'attributes' => [
                            'processorComment' => 'I have processed this request',
                            'processedMode'    => ErasureRequest::MODE_ANONYMIZATION,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(ErasureRequest::STATUS_PROCESSED, $json['data']['attributes']['status']);
        $this->assertEquals(ErasureRequest::MODE_ANONYMIZATION, $json['data']['attributes']['processedMode']);
        $this->assertEquals('I have processed this request', $json['data']['attributes']['processorComment']);

        $user = User::where('id', 5)->with('groups')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Anonymous2', $user->username);
        $this->assertEquals("{$user->username}@flarum-gdpr.local", $user->email);
        $this->assertEquals(0, $user->is_email_confirmed);
        $this->assertEmpty($user->last_seen_at);
        $this->assertNull($user->avatar_url);
        $this->assertEmpty($user->groups);
    }

    #[Test]
    public function anonymization_with_custom_username_works()
    {
        $this->setting('flarum-gdpr.default-anonymous-username', 'Custom');

        $response = $this->send(
            $this->request('PATCH', '/api/user-erasure-requests/2', [
                'authenticatedAs' => 3,
                'json'            => [
                    'data' => [
                        'attributes' => [
                            'processorComment' => 'I have processed this request',
                            'processedMode'    => ErasureRequest::MODE_ANONYMIZATION,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $user = User::where('id', 5)->with('groups')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Custom2', $user->username);
    }

    #[Test]
    public function authorized_user_cannot_process_confirmed_erasure_request_in_anonymization_mode_if_not_allowed()
    {
        $this->setting('flarum-gdpr.allow-anonymization', false);

        $response = $this->send(
            $this->request('PATCH', '/api/user-erasure-requests/2', [
                'authenticatedAs' => 3,
                'json'            => [
                    'data' => [
                        'attributes' => [
                            'processorComment' => 'I have processed this request',
                            'processedMode'    => ErasureRequest::MODE_ANONYMIZATION,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(422, $response->getStatusCode());
    }

    #[Test]
    public function authorized_user_cannot_process_confirmed_erasure_request_in_deletion_mode_if_not_allowed()
    {
        $this->setting('flarum-gdpr.allow-deletion', false);

        $response = $this->send(
            $this->request('PATCH', '/api/user-erasure-requests/2', [
                'authenticatedAs' => 3,
                'json'            => [
                    'data' => [
                        'attributes' => [
                            'processorComment' => 'I have processed this request',
                            'processedMode'    => ErasureRequest::MODE_DELETION,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(422, $response->getStatusCode());
    }

    #[Test]
    public function user_is_anonymized_when_nicknames_is_enabled()
    {
        $this->extension('flarum-nicknames');
        $this->app();

        User::unguard();
        User::find(5)->update(['nickname' => 'Custom-nickname']);
        User::reguard();

        $this->assertEquals('Custom-nickname', User::find(5)->nickname);

        $response = $this->send(
            $this->request('PATCH', '/api/user-erasure-requests/2', [
                'authenticatedAs' => 3,
                'json'            => [
                    'data' => [
                        'attributes' => [
                            'processorComment' => 'I have processed this request',
                            'processedMode'    => ErasureRequest::MODE_ANONYMIZATION,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $user = User::find(5);
        $this->assertNotNull($user);
        $this->assertEquals('Anonymous2', $user->username);
        $this->assertNull($user->nickname);
    }

    #[Test]
    public function cancelled_erasure_requests_are_not_processed()
    {
        $this->setting('flarum-gdpr.allow-deletion', true);

        $response = $this->send(
            $this->request('PATCH', '/api/user-erasure-requests/3', [
                'authenticatedAs' => 3,
                'json'            => [
                    'data' => [
                        'attributes' => [
                            'processorComment' => 'I have processed this request',
                            'processedMode'    => ErasureRequest::MODE_DELETION,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }
}
