<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Api;

use Flarum\Api\Resource\UserResource;
use Flarum\Testing\unit\TestCase;
use Flarum\User\AvatarValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class AvatarUrlFetchTest extends TestCase
{
    public static function responses(): array
    {
        return [
            'uppercase host' => ['200 OK', 'avatar', 'avatar', 'AVATAR-FETCH.INVALID'],
            'proxy bypass' => ['200 OK', 'avatar', 'direct', 'avatar-fetch.invalid', true, true],
            'lowercase proxy bypass' => ['200 OK', 'avatar', 'direct', 'avatar-fetch.invalid', true, true, true],
            'lowercase configured proxy' => ['200 OK', 'avatar', 'proxied', 'avatar-fetch.invalid', true, false, true],
            'configured proxy' => ['200 OK', 'avatar', 'proxied', 'avatar-fetch.invalid', true],
            'IPv4 literal' => ['200 OK', 'avatar', 'avatar', '127.0.0.1'],
            'hostname resolving to IPv6' => ['200 OK', 'avatar', 'avatar', 'avatar-fetch.invalid', false, false, false, '::1'],
            'IPv6 literal' => ['200 OK', 'avatar', 'avatar', '[::1]'],
            'trailing dot host' => ['200 OK', 'avatar', 'avatar', 'avatar-fetch.invalid.'],
            'small body' => ['200 OK', 'avatar', 'avatar'],
            'exact size limit' => ['200 OK', str_repeat('a', 1024), str_repeat('a', 1024)],
            'oversized body without content length' => ['200 OK', str_repeat('a', 65536), null],
            'redirect' => ['302 Found', '', null],
        ];
    }

    #[Test]
    #[DataProvider('responses')]
    public function it_pins_direct_connections_and_honors_configured_proxies(string $status, string $body, ?string $expected, string $host = 'avatar-fetch.invalid', bool $proxy = false, bool $bypassProxy = false, bool $lowercaseProxy = false, string $resolvedIp = '127.0.0.1'): void
    {
        if (! extension_loaded('curl') || ! function_exists('pcntl_fork') || ! function_exists('posix_kill')) {
            $this->markTestSkipped('Requires cURL, pcntl and posix for a local HTTP server.');
        }

        $listen = $host === '[::1]' || $resolvedIp === '::1' ? '[::1]' : '127.0.0.1';
        $server = @stream_socket_server("tcp://$listen:0", $errno, $error);

        if ($server === false && $listen === '[::1]') {
            $this->markTestSkipped('IPv6 loopback is unavailable: '.$error);
        }

        $this->assertIsResource($server, $error);
        $address = stream_socket_get_name($server, false);
        $port = substr($address, strrpos($address, ':') + 1);
        $pid = pcntl_fork();
        $this->assertNotSame(-1, $pid);

        if ($pid === 0) {
            $connection = stream_socket_accept($server, 10);

            if ($connection) {
                $requestLine = fgets($connection);

                if ($proxy) {
                    $body = str_starts_with($requestLine, 'GET http://avatar-fetch.invalid:') ? 'proxied' : 'direct';
                }

                while (($line = fgets($connection)) !== false && trim($line) !== '') {
                    // Consume the request headers.
                }

                @fwrite($connection, "HTTP/1.1 $status\r\nConnection: close\r\nLocation: http://avatar-fetch.invalid:$port/redirected\r\n\r\n".$body);
                fclose($connection);
            }

            // If redirects are mistakenly enabled, answer the second request
            // successfully so the test fails instead of passing on a timeout.
            if (str_starts_with($status, '302') && ($connection = stream_socket_accept($server, 10))) {
                while (($line = fgets($connection)) !== false && trim($line) !== '') {
                }

                @fwrite($connection, "HTTP/1.1 200 OK\r\nConnection: close\r\n\r\nredirected");
                fclose($connection);
            }

            fclose($server);
            exit(0);
        }

        $proxies = [];
        $proxyNames = ['HTTP_PROXY', 'http_proxy', 'HTTPS_PROXY', 'https_proxy', 'ALL_PROXY', 'all_proxy', 'NO_PROXY', 'no_proxy'];
        $serverProxies = array_intersect_key($_SERVER, array_flip($proxyNames));

        foreach ($proxyNames as $name) {
            $proxies[$name] = getenv($name);
            putenv($name);
            unset($_SERVER[$name]);
        }

        if ($proxy) {
            putenv("http_proxy=http://127.0.0.1:$port");

            if (! $lowercaseProxy) {
                putenv("HTTP_PROXY=http://127.0.0.1:$port");
                $_SERVER['HTTP_PROXY'] = $_SERVER['http_proxy'] = "http://127.0.0.1:$port";
            }
        }

        if ($bypassProxy) {
            putenv('no_proxy='.$host);

            if (! $lowercaseProxy) {
                putenv('NO_PROXY='.$host);
                $_SERVER['NO_PROXY'] = $_SERVER['no_proxy'] = $host;
            }
        }

        try {
            // Simulate a checked DNS answer without depending on external DNS.
            // Direct connections need CURLOPT_RESOLVE; proxied requests must
            // use the configured proxy instead.
            $resource = (new ReflectionClass(PinnedAvatarResource::class))->newInstanceWithoutConstructor();
            $resource->resolvedIp = $resolvedIp;
            $validator = $this->createStub(AvatarValidator::class);
            $validator->method('getMaxSize')->willReturn(1);
            (new ReflectionProperty(UserResource::class, 'imageValidator'))->setValue($resource, $validator);

            $result = (new ReflectionMethod(UserResource::class, 'retrieveAvatarFromUrl'))
                ->invoke($resource, "http://$host:$port/avatar");

            $this->assertSame($expected, $result);
        } finally {
            foreach ($proxies as $name => $value) {
                putenv($value === false ? $name : $name.'='.$value);
                unset($_SERVER[$name]);

                if (array_key_exists($name, $serverProxies)) {
                    $_SERVER[$name] = $serverProxies[$name];
                }
            }

            fclose($server);
            posix_kill($pid, SIGTERM);
            pcntl_waitpid($pid, $status);
        }
    }
}

class PinnedAvatarResource extends UserResource
{
    public string $resolvedIp = '127.0.0.1';

    protected function resolveToAllowedIp(string $host): ?string
    {
        return $this->resolvedIp;
    }
}
