<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\info;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;

class ShowTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ]
        ]);
    }

    #[Test]
    public function guest_cannot_access_system_info(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/system-info/system')
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function normal_user_cannot_access_system_info(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/system-info/system', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function admin_can_access_system_info(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/system-info/system', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);

        // Check that we have the expected structure
        $this->assertEquals('system-info', Arr::get($json, 'data.type'));
        $this->assertEquals('system', Arr::get($json, 'data.id'));
        $this->assertArrayHasKey('content', Arr::get($json, 'data.attributes'));

        // Check that content is a non-empty string
        $content = Arr::get($json, 'data.attributes.content');
        $this->assertIsString($content);
        $this->assertNotEmpty($content);

        // Check that the content contains expected information
        $this->assertStringContainsString('Flarum core:', $content);
        $this->assertStringContainsString('PHP version:', $content);
    }
}
