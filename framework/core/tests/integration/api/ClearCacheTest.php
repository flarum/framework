<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ClearCacheTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ],
        ]);
    }

    #[Test]
    public function admin_can_clear_the_cache()
    {
        $response = $this->send(
            $this->request('DELETE', '/api/cache', ['authenticatedAs' => 1])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function the_response_is_not_buffered_downstream()
    {
        $response = $this->send(
            $this->request('DELETE', '/api/cache', ['authenticatedAs' => 1])
        );

        // A proxy that buffered for compression would hold every step back
        // until the work had finished, which is the whole thing this avoids.
        $this->assertStringContainsString('no-transform', $response->getHeaderLine('Cache-Control'));
        $this->assertSame('no', $response->getHeaderLine('X-Accel-Buffering'));
    }

    /**
     * Clearing the cache rebuilds the compiled assets, and on remote storage
     * that is tens of seconds. The panel showed a spinner and then reloaded,
     * so it now gets the same detail the console prints — but as data: a
     * sentence assembled here could only ever be in English.
     */
    /**
     * @return list<array<string, mixed>>
     */
    private function steps(): array
    {
        $response = $this->send(
            $this->request('DELETE', '/api/cache', ['authenticatedAs' => 1])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('application/x-ndjson', $response->getHeaderLine('Content-Type'));

        // The body is a callback: reading it runs the work and captures what it
        // wrote, which is what the emitter does when serving the request.
        ob_start();
        $response->getBody()->getContents();
        $written = ob_get_clean();

        return array_map(
            fn (string $line) => json_decode($line, true),
            array_filter(explode("\n", trim($written)))
        );
    }

    #[Test]
    public function it_reports_what_was_cleared_and_rebuilt()
    {
        $types = array_column($this->steps(), 'type');

        $this->assertContains('cleared', $types);
        $this->assertContains('rebuilt', $types);
    }

    /**
     * Each step is a complete JSON object on its own line, so the panel can act
     * on it the moment it arrives rather than waiting for a whole document to
     * parse.
     */
    #[Test]
    public function every_step_is_a_line_of_its_own()
    {
        $steps = $this->steps();

        $this->assertNotEmpty($steps);

        foreach ($steps as $step) {
            $this->assertIsArray($step, 'every line must parse on its own');
            $this->assertArrayHasKey('type', $step);
        }
    }

    /**
     * The client watches for this rather than inferring completion from the
     * connection closing, which it cannot tell apart from one that dropped.
     */
    #[Test]
    public function the_stream_ends_with_a_done_step()
    {
        $steps = $this->steps();

        $this->assertSame('done', end($steps)['type']);
    }

    /**
     * Each entry says what happened rather than describing it, so the panel can
     * put its own words to a bundle that was rebuilt, left alone, or had
     * nothing to compile.
     */
    #[Test]
    public function each_rebuilt_bundle_carries_its_own_state()
    {
        $rebuilt = array_values(array_filter($this->steps(), fn (array $s) => $s['type'] === 'rebuilt'));

        foreach ($rebuilt as $row) {
            $this->assertArrayHasKey('frontend', $row);
            $this->assertArrayHasKey('bundle', $row);
            $this->assertArrayHasKey('state', $row);
            $this->assertArrayHasKey('milliseconds', $row);
            $this->assertContains($row['state'], ['unchanged', 'rebuilt', 'rewritten', 'empty', 'chunks']);
        }

        // Every locale is represented, because that is where a rebuild spends
        // its time: the translations live in the per-locale bundles.
        $locales = array_filter(array_column($rebuilt, 'locale'));

        $this->assertContains('en', $locales);
    }

    #[Test]
    public function a_normal_user_cannot_clear_the_cache()
    {
        $response = $this->send(
            $this->request('DELETE', '/api/cache', ['authenticatedAs' => 2])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Carrying a valid CSRF token but no session, so the request reaches the
     * controller and is turned away for what it is rather than for a missing
     * token. Streaming the body does not loosen that: the middleware wraps the
     * response object, and only the emitter touches the body.
     */
    #[Test]
    public function a_guest_cannot_clear_the_cache()
    {
        $response = $this->send(
            $this->requestWithCsrfToken($this->request('DELETE', '/api/cache'))
        );

        $this->assertEquals(403, $response->getStatusCode());
    }
}
