<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Http;

use Flarum\Http\RequestUtil;
use Flarum\Testing\unit\TestCase;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;

class RequestUtilTest extends TestCase
{
    private function requestWithAccept(?string $accept): ServerRequestInterface
    {
        $request = new ServerRequest([], [], '/', 'GET');

        return $accept === null ? $request : $request->withHeader('Accept', $accept);
    }

    public static function apiAcceptHeaders(): array
    {
        return [
            'JSON:API media type' => ['application/vnd.api+json'],
            'plain JSON' => ['application/json'],
            'wildcard (our XHR requests)' => ['*/*'],
            'no Accept header at all' => [null],
        ];
    }

    public static function htmlAcceptHeaders(): array
    {
        return [
            'explicit text/html' => ['text/html'],
            'typical browser header' => ['text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8'],
            // No offered type matches -> bestMatch() returns null -> treated as non-API.
            'unrelated type' => ['application/xml'],
            // Malformed media range -> bestMatch() throws -> must be swallowed and treated as non-API.
            'malformed header' => ['this is not a valid accept header'],
        ];
    }

    #[Test]
    #[DataProvider('apiAcceptHeaders')]
    public function api_accept_headers_are_detected_as_api_requests(?string $accept): void
    {
        $request = $this->requestWithAccept($accept);

        $this->assertTrue(RequestUtil::isApiRequest($request));
        $this->assertFalse(RequestUtil::isHtmlRequest($request));
    }

    #[Test]
    #[DataProvider('htmlAcceptHeaders')]
    public function html_accept_headers_are_not_detected_as_api_requests(string $accept): void
    {
        $request = $this->requestWithAccept($accept);

        $this->assertFalse(RequestUtil::isApiRequest($request));
        $this->assertTrue(RequestUtil::isHtmlRequest($request));
    }

    #[Test]
    public function get_preferred_content_type_returns_the_best_match(): void
    {
        $request = $this->requestWithAccept('text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8');

        $this->assertSame(
            'text/html',
            RequestUtil::getPreferredContentType($request, ['application/vnd.api+json', 'application/json', 'text/html'])
        );
    }

    #[Test]
    public function get_preferred_content_type_never_throws_on_a_malformed_header(): void
    {
        // The error handler relies on this never throwing.
        $request = $this->requestWithAccept('garbage;;;');

        $this->assertSame(
            '',
            RequestUtil::getPreferredContentType($request, ['application/vnd.api+json', 'text/html'])
        );
    }

    #[Test]
    public function get_preferred_content_type_returns_empty_string_when_nothing_matches(): void
    {
        $request = $this->requestWithAccept('application/xml');

        $this->assertSame(
            '',
            RequestUtil::getPreferredContentType($request, ['application/vnd.api+json', 'text/html'])
        );
    }
}
