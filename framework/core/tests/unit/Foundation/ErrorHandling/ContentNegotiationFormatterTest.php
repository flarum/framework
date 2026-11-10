<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Foundation\ErrorHandling;

use Exception;
use Flarum\Foundation\ErrorHandling\ContentNegotiationFormatter;
use Flarum\Foundation\ErrorHandling\HandledError;
use Flarum\Foundation\ErrorHandling\HttpFormatter;
use Flarum\Testing\unit\TestCase;
use Laminas\Diactoros\ServerRequest;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ContentNegotiationFormatterTest extends TestCase
{
    private HandledError $error;
    private ResponseInterface $jsonResponse;
    private ResponseInterface $htmlResponse;
    private HttpFormatter $json;
    private HttpFormatter $html;

    protected function setUp(): void
    {
        parent::setUp();

        $this->error = new HandledError(new Exception(), 'unknown', 500);
        $this->jsonResponse = m::mock(ResponseInterface::class);
        $this->htmlResponse = m::mock(ResponseInterface::class);

        $this->json = m::mock(HttpFormatter::class);
        $this->html = m::mock(HttpFormatter::class);
    }

    private function formatter(): ContentNegotiationFormatter
    {
        return new ContentNegotiationFormatter($this->json, $this->html);
    }

    private function requestWithAccept(string $accept): ServerRequestInterface
    {
        return (new ServerRequest([], [], '/', 'GET'))->withHeader('Accept', $accept);
    }

    #[Test]
    public function api_requests_are_formatted_as_json(): void
    {
        $request = $this->requestWithAccept('application/vnd.api+json');

        $this->json->shouldReceive('format')->once()->with($this->error, $request)->andReturn($this->jsonResponse);
        $this->html->shouldNotReceive('format');

        $this->assertSame($this->jsonResponse, $this->formatter()->format($this->error, $request));
    }

    #[Test]
    public function browser_requests_are_formatted_as_html(): void
    {
        $request = $this->requestWithAccept('text/html,application/xhtml+xml,*/*;q=0.8');

        $this->html->shouldReceive('format')->once()->with($this->error, $request)->andReturn($this->htmlResponse);
        $this->json->shouldNotReceive('format');

        $this->assertSame($this->htmlResponse, $this->formatter()->format($this->error, $request));
    }
}
