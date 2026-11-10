<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\integration\api;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class StateTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-extension-manager');

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ],
        ]);
    }

    #[Test]
    public function an_admin_can_read_the_state(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/extension-manager/state', ['authenticatedAs' => 1])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertIsArray(json_decode((string) $response->getBody(), true));
    }

    #[Test]
    #[DataProvider('endpoints')]
    public function a_member_cannot_reach_any_endpoint(string $method, string $path): void
    {
        $response = $this->send(
            $this->request($method, $path, ['authenticatedAs' => 2])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    #[DataProvider('endpoints')]
    public function a_guest_cannot_reach_any_endpoint(string $method, string $path): void
    {
        $response = $this->send($this->request($method, $path));

        // A POST without a session is refused by the CSRF check (400) before it
        // reaches the controller; a GET by the controller (401 or 403).
        $this->assertContains($response->getStatusCode(), [400, 401, 403]);
    }

    public static function endpoints(): array
    {
        return [
            ['GET', '/api/extension-manager/state'],
            ['POST', '/api/extension-manager/check'],
            ['GET', '/api/extension-manager/release-notes'],
            ['POST', '/api/extension-manager/update'],
            ['POST', '/api/extension-manager/step'],
            ['POST', '/api/extension-manager/rollback'],
            ['GET', '/api/extension-manager/trash'],
            ['POST', '/api/extension-manager/trash'],
            ['GET', '/api/extension-manager/core'],
            ['GET', '/api/extension-manager/config'],
            ['POST', '/api/extension-manager/config'],
            ['GET', '/api/extension-manager/discover'],
            ['POST', '/api/extension-manager/discover/compat'],
            // Millwright's paths, kept for a browser tab that is mid-handover.
            ['GET', '/api/millwright/state'],
            ['POST', '/api/millwright/step'],
            ['POST', '/api/millwright/rollback'],
        ];
    }
}
