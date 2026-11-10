<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Tests\unit;

use Flarum\OAuthProvider\Server\RedirectUriMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RedirectUriMatcherTest extends TestCase
{
    /**
     * @return array<string, array{string, list<string>, bool, bool}>
     */
    public static function cases(): array
    {
        return [
            'exact https' => ['https://app.example.com/cb', ['https://app.example.com/cb'], false, true],
            'other https path' => ['https://app.example.com/other', ['https://app.example.com/cb'], true, false],
            'https port is not relaxed' => ['https://app.example.com:8443/cb', ['https://app.example.com/cb'], true, false],
            'ipv4 loopback, public' => ['http://127.0.0.1:5000/cb', ['http://127.0.0.1/cb'], true, true],
            'ipv6 loopback, public' => ['http://[::1]:5000/cb', ['http://[::1]:80/cb'], true, true],
            'localhost, public' => ['http://localhost:5000/cb', ['http://localhost/cb'], true, true],
            'loopback, confidential' => ['http://127.0.0.1:5000/cb', ['http://127.0.0.1/cb'], false, false],
            'loopback, other path' => ['http://127.0.0.1:5000/other', ['http://127.0.0.1/cb'], true, false],
            'loopback, other scheme' => ['https://127.0.0.1:5000/cb', ['http://127.0.0.1/cb'], true, false],
            'loopback, other host' => ['http://localhost:5000/cb', ['http://127.0.0.1/cb'], true, false],
            'loopback, other query' => ['http://127.0.0.1:5000/cb?x=1', ['http://127.0.0.1/cb'], true, false],
            'non-loopback http' => ['http://app.example.com:5000/cb', ['http://app.example.com/cb'], true, false],
        ];
    }

    /**
     * @param list<string> $registered
     */
    #[Test]
    #[DataProvider('cases')]
    public function redirect_uri_matching(string $requested, array $registered, bool $public, bool $expected): void
    {
        $this->assertSame($expected, (new RedirectUriMatcher())->matches($requested, $registered, $public));
    }
}
