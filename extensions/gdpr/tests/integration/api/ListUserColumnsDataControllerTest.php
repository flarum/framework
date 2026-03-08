<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Tests\integration\Api;

use Flarum\Gdpr\Extend\UserData;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ListUserColumnsDataControllerTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ],
        ]);

        $this->extension('flarum-gdpr');
    }

    #[Test]
    public function non_admin_cannot_access_user_columns()
    {
        $response = $this->send(
            $this->request('GET', '/api/gdpr-datatypes/user-columns', ['authenticatedAs' => 2])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function admin_can_access_user_columns()
    {
        $response = $this->send(
            $this->request('GET', '/api/gdpr-datatypes/user-columns', ['authenticatedAs' => 1])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('allColumns', $body['data']);
        $this->assertArrayHasKey('removableColumns', $body['data']);
        $this->assertArrayHasKey('piiKeys', $body['data']);
    }

    #[Test]
    public function response_includes_built_in_pii_keys()
    {
        $response = $this->send(
            $this->request('GET', '/api/gdpr-datatypes/user-columns', ['authenticatedAs' => 1])
        );

        $body = json_decode($response->getBody()->getContents(), true);
        $piiKeys = $body['data']['piiKeys'];

        $this->assertContains('email', $piiKeys);
        $this->assertContains('username', $piiKeys);
        $this->assertContains('last_seen_at', $piiKeys);
        $this->assertContains('joined_at', $piiKeys);
        $this->assertContains('preferences', $piiKeys);
    }

    #[Test]
    public function response_includes_extra_pii_keys_registered_via_extender()
    {
        $this->extend(
            (new UserData())
                ->addPiiKeysForSerialization('custom_pii_field')
        );

        $response = $this->send(
            $this->request('GET', '/api/gdpr-datatypes/user-columns', ['authenticatedAs' => 1])
        );

        $body = json_decode($response->getBody()->getContents(), true);
        $piiKeys = $body['data']['piiKeys'];

        $this->assertContains('custom_pii_field', $piiKeys);
        $this->assertContains('email', $piiKeys); // built-in keys still present
    }

    #[Test]
    public function response_includes_removable_columns_registered_via_extender()
    {
        $this->extend(
            (new UserData())
                ->removeUserColumns(['my_custom_column'])
        );

        $response = $this->send(
            $this->request('GET', '/api/gdpr-datatypes/user-columns', ['authenticatedAs' => 1])
        );

        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('my_custom_column', $body['data']['removableColumns']);
    }

    #[Test]
    public function all_columns_includes_standard_user_table_columns()
    {
        $response = $this->send(
            $this->request('GET', '/api/gdpr-datatypes/user-columns', ['authenticatedAs' => 1])
        );

        $body = json_decode($response->getBody()->getContents(), true);
        $allColumns = $body['data']['allColumns'];

        $this->assertArrayHasKey('id', $allColumns);
        $this->assertArrayHasKey('username', $allColumns);
        $this->assertArrayHasKey('email', $allColumns);

        // Each column entry has the expected metadata shape
        $this->assertArrayHasKey('type', $allColumns['id']);
        $this->assertArrayHasKey('nullable', $allColumns['id']);
    }
}
