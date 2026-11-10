<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\forum;

use Flarum\Extend;
use Flarum\Testing\integration\TestCase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ErrorNegotiationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Register a forum route that always errors, so we can assert how the
        // forum error handler formats the response based on the Accept header
        // (the regression from #3850, where forum errors were always HTML).
        $this->extend(
            (new Extend\Routes('forum'))
                ->get('/test-error-negotiation', 'test.error-negotiation', ErrorThrowingRoute::class)
        );
    }

    private function error(?string $accept): ResponseInterface
    {
        $request = $this->request('GET', '/test-error-negotiation');

        if ($accept !== null) {
            $request = $request->withHeader('Accept', $accept);
        }

        return $this->send($request);
    }

    #[Test]
    public function forum_errors_are_returned_as_json_for_api_requests(): void
    {
        $response = $this->error('application/vnd.api+json');

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($body);
        $this->assertArrayHasKey('errors', $body);
    }

    #[Test]
    public function forum_errors_are_returned_as_json_for_xhr_requests_without_an_explicit_accept(): void
    {
        // Flarum's own XHR requests send `Accept: */*`. This is the regression from #3850:
        // such requests previously received an HTML error page instead of JSON.
        $response = $this->error('*/*');

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($body);
        $this->assertArrayHasKey('errors', $body);
    }

    #[Test]
    public function forum_errors_are_returned_as_html_for_browser_requests(): void
    {
        $response = $this->error('text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8');

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('html', $response->getHeaderLine('Content-Type'));
        $this->assertNull(json_decode((string) $response->getBody(), true));
    }
}

class ErrorThrowingRoute implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        throw (new ModelNotFoundException())->setModel('Test');
    }
}
