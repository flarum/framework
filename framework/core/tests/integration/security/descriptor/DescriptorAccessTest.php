<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\security\descriptor;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Security: who can reach `GET /api/resource-schemas/{type}`, and what an
 * invalid or non-describable type answers.
 */
class DescriptorAccessTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        // The test install runs in debug mode, which adds traces to every error.
        $this->config('debug', false);

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
        ]);
    }

    public static function types(): array
    {
        return [
            'users' => ['users'],
            'groups' => ['groups'],
            'access-tokens' => ['access-tokens'],
            'posts (not describable)' => ['posts'],
            'discussions (not describable)' => ['discussions'],
            'resource-schemas itself' => ['resource-schemas'],
            'settings-like' => ['settings'],
            'unknown' => ['does-not-exist'],
            'class name' => ['Flarum\\Api\\Resource\\UserResource'],
            'class name of a resource base' => ['Flarum\\Api\\Resource\\AbstractDatabaseResource'],
            'traversal' => ['..%2F..%2Fetc%2Fpasswd'],
            'dot dot' => ['..'],
            'unicode' => ['%E2%80%AEusers'],
            'null byte' => ['users%00'],
            'very long' => [str_repeat('a', 8000)],
            'sql-ish' => ["users'%20OR%201=1--"],
        ];
    }

    #[Test]
    #[DataProvider('types')]
    public function a_guest_gets_401_for_every_type_without_detail(string $type)
    {
        $response = $this->send($this->request('GET', '/api/resource-schemas/'.$type));
        $body = (string) $response->getBody();

        $this->assertContains($response->getStatusCode(), [401, 404], $body);
        $this->assertLessThan(500, $response->getStatusCode(), $body);

        // A guest must not learn whether the type exists: same answer for every type.
        $reference = $this->send($this->request('GET', '/api/resource-schemas/does-not-exist'));
        $this->assertSame($reference->getStatusCode(), $response->getStatusCode(), $body);
        $this->assertSame((string) $reference->getBody(), $body);
        $this->assertNoInternals($body);
    }

    #[Test]
    #[DataProvider('types')]
    public function a_member_never_gets_a_500_or_internals(string $type)
    {
        $response = $this->send($this->request('GET', '/api/resource-schemas/'.$type, ['authenticatedAs' => 2]));
        $body = (string) $response->getBody();

        $this->assertLessThan(500, $response->getStatusCode(), $body);

        if (! in_array($type, ['users', 'groups', 'access-tokens'], true)) {
            $this->assertSame(404, $response->getStatusCode(), $body);
        }

        // The 404 echoes the requested id back; only what the server adds counts.
        $this->assertNoInternals(str_replace(trim(json_encode(urldecode($type)), '"'), '', $body));
    }

    #[Test]
    #[DataProvider('types')]
    public function debug_mode_does_not_turn_an_invalid_type_into_a_500(string $type)
    {
        $this->config('debug', true);

        $response = $this->send($this->request('GET', '/api/resource-schemas/'.$type, ['authenticatedAs' => 1]));

        $this->assertLessThan(500, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function the_index_and_write_endpoints_do_not_exist()
    {
        foreach ([['GET', '/api/resource-schemas'], ['POST', '/api/resource-schemas'], ['PATCH', '/api/resource-schemas/users'], ['DELETE', '/api/resource-schemas/users']] as [$method, $path]) {
            $response = $this->send($this->request($method, $path, ['authenticatedAs' => 1, 'json' => []]));

            $this->assertContains($response->getStatusCode(), [404, 405], "$method $path: ".$response->getBody());
        }
    }

    private function assertNoInternals(string $body): void
    {
        $this->assertStringNotContainsString('Flarum\\\\', $body);
        $this->assertStringNotContainsString('Tobyz\\\\', $body);
        $this->assertStringNotContainsString('.php', $body);
        $this->assertStringNotContainsString('/Users/', $body);
        $this->assertStringNotContainsString('Stack trace', $body);
        $this->assertStringNotContainsString('SQLSTATE', $body);
    }
}
