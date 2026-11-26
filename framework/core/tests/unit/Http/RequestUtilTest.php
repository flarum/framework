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
use Tobyz\JsonApiServer\Exception\BadRequestException;

class RequestUtilTest extends TestCase
{
    private function requestWithAccept(?string $accept): ServerRequestInterface
    {
        $request = new ServerRequest([], [], '/', 'GET');

        return $accept === null ? $request : $request->withHeader('Accept', $accept);
    }

    private function requestWithLimit(?string $limit): ServerRequestInterface
    {
        $request = new ServerRequest([], [], '/', 'GET');

        return $request->withQueryParams($limit === null ? [] : ['page' => ['limit' => $limit]]);
    }

    private function requestWithPageNumber(mixed $number): ServerRequestInterface
    {
        return (new ServerRequest([], [], '/', 'GET'))->withQueryParams(['page' => ['number' => $number]]);
    }

    #[Test]
    public function extract_limit_uses_an_explicitly_requested_limit(): void
    {
        $this->assertSame(20, RequestUtil::extractLimit($this->requestWithLimit('20'), 50, 100));
    }

    #[Test]
    public function extract_limit_caps_at_the_maximum(): void
    {
        $this->assertSame(50, RequestUtil::extractLimit($this->requestWithLimit('100'), 20, 50));
    }

    #[Test]
    public function extract_limit_falls_back_to_the_default_when_absent(): void
    {
        $this->assertSame(20, RequestUtil::extractLimit($this->requestWithLimit(null), 20, 50));
    }

    #[Test]
    public function extract_limit_returns_null_when_there_is_no_limit_and_no_default(): void
    {
        $this->assertNull(RequestUtil::extractLimit($this->requestWithLimit(null), null, 50));
    }

    #[Test]
    public function extract_limit_rejects_an_explicit_zero(): void
    {
        // Regression test: page[limit]=0 used to return null and then break
        // OffsetPagination's non-nullable int $limit with a 500. It must 400 instead.
        $this->expectException(BadRequestException::class);

        RequestUtil::extractLimit($this->requestWithLimit('0'), 20, 50);
    }

    #[Test]
    public function extract_limit_rejects_a_negative_limit(): void
    {
        $this->expectException(BadRequestException::class);

        RequestUtil::extractLimit($this->requestWithLimit('-5'), 20, 50);
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

    #[Test]
    public function extract_offset_accepts_the_largest_page_whose_offset_fits(): void
    {
        $page = intdiv(PHP_INT_MAX, 20) + 1;

        $this->assertSame(($page - 1) * 20, RequestUtil::extractOffset($this->requestWithPageNumber((string) $page), 20));
    }

    #[Test]
    public function extract_offset_rejects_the_first_page_whose_offset_overflows(): void
    {
        $this->expectException(BadRequestException::class);

        RequestUtil::extractOffset($this->requestWithPageNumber((string) (intdiv(PHP_INT_MAX, 20) + 2)), 20);
    }

    #[Test]
    public function extract_offset_accepts_an_int_page_number(): void
    {
        $this->assertSame(40, RequestUtil::extractOffset($this->requestWithPageNumber(3), 20));
    }

    #[Test]
    public function extract_offset_rejects_an_overflowing_int_page_number(): void
    {
        $this->expectException(BadRequestException::class);

        RequestUtil::extractOffset($this->requestWithPageNumber(PHP_INT_MAX), 20);
    }

    #[Test]
    public function extract_offset_is_zero_for_a_page_number_without_a_limit(): void
    {
        $this->assertSame(0, RequestUtil::extractOffset($this->requestWithPageNumber('2'), null));
    }

    #[Test]
    public function extract_offset_validates_a_page_number_without_a_limit(): void
    {
        $this->expectException(BadRequestException::class);

        RequestUtil::extractOffset($this->requestWithPageNumber('abc'), null);
    }
}
