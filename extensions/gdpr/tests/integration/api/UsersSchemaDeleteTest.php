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

/**
 * The users descriptor advertises delete, and the delete it points at is
 * still gdpr's replaced endpoint.
 */
class UsersSchemaDeleteTest extends TestCase
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
    public function users_descriptor_advertises_delete_for_admin()
    {
        $response = $this->send(
            $this->request('GET', '/api/resource-schemas/users', ['authenticatedAs' => 1])
        );

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        $this->assertTrue(json_decode($body, true)['data']['attributes']['capabilities']['delete']);
    }

    #[Test]
    public function delete_still_goes_through_the_gdpr_endpoint()
    {
        $response = $this->send(
            $this->request('DELETE', '/api/users/2', ['authenticatedAs' => 1])
        );

        $this->assertEquals(204, $response->getStatusCode());

        $user = User::query()->where('id', 2)->with('erasureRequest')->first();
        $this->assertNotNull($user);
        $this->assertEquals("Anonymous{$user->erasureRequest->id}", $user->username);
        $this->assertEquals(ErasureRequest::STATUS_MANUAL, $user->erasureRequest->status);
    }
}
