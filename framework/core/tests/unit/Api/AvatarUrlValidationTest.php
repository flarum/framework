<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Api;

use Flarum\Api\JsonApi;
use Flarum\Api\Resource\UserResource;
use Flarum\Foundation\ValidationException;
use Flarum\Testing\unit\TestCase;
use Illuminate\Container\Container;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class AvatarUrlValidationTest extends TestCase
{
    public static function rejectedUrls(): array
    {
        $invalid = 'Provided avatar URL must be a valid URI.';
        $blocked = 'Provided avatar URL must not point at a loopback, link-local or reserved address.';

        return [
            'relative path' => ['../image.png', $invalid],
            'bare filename' => ['image.png', $invalid],
            'empty' => ['', $invalid],
            'file scheme' => ['file://localhost/etc/passwd', 'Scheme provided was file.'],
            'ftp scheme' => ['ftp://localhost/image.png', 'Scheme provided was ftp.'],
            'gopher scheme' => ['gopher://localhost/_stats', 'Scheme provided was gopher.'],
            'IPv4 loopback literal' => ['https://127.0.0.1/image.png', $blocked],
            'IPv6 loopback literal' => ['https://[::1]/image.png', $blocked],
            'IPv4-mapped loopback literal' => ['http://[::ffff:127.0.0.1]/image.png', $blocked],
            'link-local literal' => ['http://169.254.169.254/latest/meta-data', $blocked],
            'loopback literal behind userinfo' => ['https://user:pw@127.0.0.1/image.png', $blocked],
        ];
    }

    #[Test]
    #[DataProvider('rejectedUrls')]
    public function it_rejects_invalid_urls_and_blocked_ip_literals_without_dns(string $url, string $expectedError): void
    {
        try {
            $this->validate($url, 'avatar_url_2x');
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $e) {
            $this->assertSame(['avatar_url_2x'], array_keys($e->getAttributes()));
            $this->assertStringContainsString($expectedError, $e->getAttributes()['avatar_url_2x']);
        }
    }

    public static function acceptedUrls(): array
    {
        return [
            'public IPv4 literal' => ['https://93.184.215.14/image.png'],
            'private IPv4 literal' => ['http://10.0.0.5/image.png'],
            'uppercase scheme' => ['HTTPS://93.184.215.14/image.png'],
            'unencoded characters in the path' => ['https://93.184.215.14/my avatar|1.png'],
            // Hostnames are left to the fetch, which checks the resolved address.
            'hostname' => ['https://localhost/image.png'],
        ];
    }

    #[Test]
    #[DataProvider('acceptedUrls')]
    public function it_accepts_urls_without_resolving_their_host(string $url): void
    {
        $this->validate($url, 'avatar_url');

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function it_drops_an_invalid_hidpi_url_with_a_warning_instead_of_failing(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('avatar_url_3x'));

        $container = new Container();
        $container->instance(LoggerInterface::class, $logger);
        $api = $this->createStub(JsonApi::class);
        $api->method('getContainer')->willReturn($container);

        $resource = (new ReflectionClass(NoDnsAvatarResource::class))->newInstanceWithoutConstructor();
        (new ReflectionProperty(UserResource::class, 'api'))->setValue($resource, $api);
        $method = new ReflectionMethod(UserResource::class, 'validHiDpiAvatarUrl');

        $this->assertNull($method->invoke($resource, 'https://127.0.0.1/image@3x.png', 'avatar_url_3x'));
        $this->assertSame('https://93.184.215.14/image@2x.png', $method->invoke($resource, 'https://93.184.215.14/image@2x.png', 'avatar_url_2x'));
        $this->assertNull($method->invoke($resource, null, 'avatar_url_2x'));
    }

    private function validate(string $url, string $attribute): void
    {
        $resource = (new ReflectionClass(NoDnsAvatarResource::class))->newInstanceWithoutConstructor();

        (new ReflectionMethod(UserResource::class, 'assertValidAvatarUrl'))->invoke($resource, $url, $attribute);
    }
}

class NoDnsAvatarResource extends UserResource
{
    protected function resolveToAllowedIp(string $host): ?string
    {
        throw new LogicException("Validation must not resolve $host.");
    }
}
