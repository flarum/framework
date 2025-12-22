<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Tests\integration\Api;

use Flarum\Gdpr\Data\Forum;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class ListDataTypesControllerTest extends TestCase
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
    public function non_admin_cannot_list_types()
    {
        $response = $this->send(
            $this->request('GET', '/api/gdpr-datatypes', ['authenticatedAs' => 2])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function admin_can_list_types()
    {
        $response = $this->send(
            $this->request('GET', '/api/gdpr-datatypes', ['authenticatedAs' => 1])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertCount(6, $body['data']);

        $this->assertEquals(Forum::class, $body['data'][0]['id']);
        $this->assertEquals(Forum::exportDescription(), $body['data'][0]['attributes']['exportDescription']);
        $this->assertEquals(Forum::anonymizeDescription(), $body['data'][0]['attributes']['anonymizeDescription']);
        $this->assertEquals(Forum::deleteDescription(), $body['data'][0]['attributes']['deleteDescription']);
        $this->assertNull($body['data'][0]['attributes']['extension']);
    }
}
