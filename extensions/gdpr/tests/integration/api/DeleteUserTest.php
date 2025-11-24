<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\tests\integration\api;

use Flarum\Gdpr\Models\ErasureRequest;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class DeleteUserTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
        ]);

        $this->extension('flarum-gdpr');
    }

    #[Test]
    public function delete_user_endpoint_is_processed_by_this_extension()
    {
        $response = $this->send(
            $this->request(
                'DELETE',
                '/api/users/2',
                [
                    'authenticatedAs' => 1,
                ]
            )
        );

        $this->assertEquals(204, $response->getStatusCode());

        $user = User::query()->where('id', 2)->with('erasureRequest')->first();
        $this->assertNotNull($user);
        $this->assertEquals("Anonymous{$user->erasureRequest->id}", $user->username);
        $this->assertEquals(ErasureRequest::STATUS_MANUAL, $user->erasureRequest->status);
    }

    #[Test]
    public function delete_user_endpoint_uses_default_when_no_mode_provided()
    {
        $response = $this->send(
            $this->request(
                'DELETE',
                '/api/users/2',
                [
                    'authenticatedAs' => 1,
                    'json' => [],
                ]
            )
        );

        $this->assertEquals(204, $response->getStatusCode());

        $user = User::query()->where('id', 2)->with('erasureRequest')->first();
        $this->assertNotNull($user);
        $this->assertEquals("Anonymous{$user->erasureRequest->id}", $user->username);
        $this->assertEquals(ErasureRequest::STATUS_MANUAL, $user->erasureRequest->status);
    }

    #[Test]
    public function delete_user_endpoint_can_be_called_with_anonymization_mode()
    {
        $response = $this->send(
            $this->request(
                'DELETE',
                '/api/users/2',
                [
                    'authenticatedAs' => 1,
                    'json' => [
                        'gdprMode' => ErasureRequest::MODE_ANONYMIZATION,
                    ],
                ]
            )
        );

        $this->assertEquals(204, $response->getStatusCode());

        $user = User::query()->where('id', 2)->with('erasureRequest')->first();
        $this->assertNotNull($user);
        $this->assertEquals("Anonymous{$user->erasureRequest->id}", $user->username);
        $this->assertEquals(ErasureRequest::STATUS_MANUAL, $user->erasureRequest->status);
    }

    #[Test]
    public function delete_user_endpoint_with_deletion_mode_not_enabled_by_default()
    {
        $response = $this->send(
            $this->request(
                'DELETE',
                '/api/users/2',
                [
                    'authenticatedAs' => 1,
                    'json' => [
                        'gdprMode' => ErasureRequest::MODE_DELETION,
                    ],
                ]
            )
        );

        $this->assertEquals(422, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals('validation_error', $data['errors'][0]['code']);
        $this->assertEquals('/data/attributes/mode', $data['errors'][0]['source']['pointer']);
    }

    #[Test]
    public function delete_user_endpoint_can_be_called_with_deletion_mode_enabled()
    {
        $this->setting('flarum-gdpr.allow-deletion', true);

        $response = $this->send(
            $this->request(
                'DELETE',
                '/api/users/2',
                [
                    'authenticatedAs' => 1,
                    'json' => [
                        'gdprMode' => ErasureRequest::MODE_DELETION,
                    ],
                ]
            )
        );

        $this->assertEquals(204, $response->getStatusCode());

        $user = User::query()->where('id', 2)->first();
        $this->assertNull($user);
    }

    #[Test]
    public function invalid_erasure_mode_throws_validation_error()
    {
        $response = $this->send(
            $this->request(
                'DELETE',
                '/api/users/2',
                [
                    'authenticatedAs' => 1,
                    'json' => [
                        'gdprMode' => 'invalid-mode',
                    ],
                ]
            )
        );

        $this->assertEquals(422, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals('validation_error', $data['errors'][0]['code']);
        $this->assertEquals('/data/attributes/mode', $data['errors'][0]['source']['pointer']);
        $this->assertStringContainsString('invalid-mode', $data['errors'][0]['detail']);
    }
}
