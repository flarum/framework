<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\tests\integration\api;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class UserAttributesTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-gdpr');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'moderator', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'moderator@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'anon', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'anon@machine.local', 'is_email_confirmed' => 0, 'anonymized' => 1],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 4],
            ],
            'group_permission' => [
                ['permission' => 'seeAnonymizedUserBadges', 'group_id' => 4],
            ],
        ]);
    }

    #[Test]
    public function user_without_permission_does_not_see_anonimized_flag()
    {
        $response = $this->send(
            $this->request('GET', '/api/users/2')
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);

        // assert the user does not have the property `anonymized`
        $this->assertArrayNotHasKey('anonymized', $body['data']['attributes']);
    }

    #[Test]
    public function admin_sees_anonimized_flag()
    {
        $response = $this->send(
            $this->request('GET', '/api/users/2', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);

        // assert the user has the property `anonymized`
        $this->assertArrayHasKey('anonymized', $body['data']['attributes']);
        $this->assertFalse($body['data']['attributes']['anonymized']);
    }

    #[Test]
    public function user_with_permission_sees_anonimized_flag()
    {
        $response = $this->send(
            $this->request('GET', '/api/users/2', [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);

        // assert the user has the property `anonymized`
        $this->assertArrayHasKey('anonymized', $body['data']['attributes']);
        $this->assertFalse($body['data']['attributes']['anonymized']);
    }

    #[Test]
    public function user_with_permission_sees_anonimized_flag_on_anonimized_user()
    {
        $response = $this->send(
            $this->request('GET', '/api/users/4', [
                'authenticatedAs' => 3,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);

        // assert the user has the property `anonymized`
        $this->assertArrayHasKey('anonymized', $body['data']['attributes']);
        $this->assertTrue($body['data']['attributes']['anonymized']);
    }
}
