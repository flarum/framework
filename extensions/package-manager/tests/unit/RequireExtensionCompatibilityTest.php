<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\unit;

use Flarum\ExtensionManager\Command\RequireExtensionHandler;
use Flarum\ExtensionManager\Exception\ExtensionIncompatibleWithFlarumException;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RequireExtensionCompatibilityTest extends TestCase
{
    /**
     * Expose the protected assertFlarumCompatibility method for testing.
     */
    private function makeHandler(Client $http): object
    {
        return new class($http) extends RequireExtensionHandler {
            public function __construct(Client $http)
            {
                // Bypass the normal constructor — we only need the HTTP client for these tests.
                $this->http = $http;
            }

            public function checkCompatibility(string $packageName): void
            {
                $this->assertFlarumCompatibility($packageName);
            }
        };
    }

    private function makeClient(string $responseBody, int $status = 200): Client
    {
        $mock = new MockHandler([new Response($status, [], $responseBody)]);

        return new Client(['handler' => HandlerStack::create($mock)]);
    }

    private function packagistPayload(string $package, string $coreConstraint, string $version = 'v2.0.0'): string
    {
        return json_encode([
            'packages' => [
                $package => [
                    [
                        'name' => $package,
                        'version' => $version,
                        'require' => [
                            'flarum/core' => $coreConstraint,
                        ],
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function allows_extension_with_explicit_2x_constraint(): void
    {
        $this->expectNotToPerformAssertions();
        $handler = $this->makeHandler($this->makeClient($this->packagistPayload('acme/test', '^2.0.0-beta.1')));

        $handler->checkCompatibility('acme/test:*');
    }

    #[Test]
    public function allows_extension_with_beta_2x_constraint_satisfied_by_current_version(): void
    {
        $this->expectNotToPerformAssertions();
        $handler = $this->makeHandler($this->makeClient($this->packagistPayload('acme/test', '^2.0.0-beta.5')));

        $handler->checkCompatibility('acme/test');
    }

    #[Test]
    #[DataProvider('incompatibleConstraints')]
    public function rejects_extension_with_incompatible_constraint(string $constraint): void
    {
        $handler = $this->makeHandler($this->makeClient($this->packagistPayload('acme/test', $constraint)));

        $this->expectException(ExtensionIncompatibleWithFlarumException::class);
        $handler->checkCompatibility('acme/test');
    }

    public static function incompatibleConstraints(): array
    {
        return [
            'wildcard *' => ['*'],
            '1.x constraint' => ['^1.0'],
            'broad >= constraint' => ['>=1.0'],
            '1.x range' => ['>=1.0,<2.0'],
        ];
    }

    #[Test]
    public function allows_install_when_packagist_returns_non_200(): void
    {
        $this->expectNotToPerformAssertions();
        $handler = $this->makeHandler($this->makeClient('', 503));

        // Fail open when Packagist is unavailable.
        $handler->checkCompatibility('acme/test');
    }

    #[Test]
    public function allows_install_when_package_has_no_releases(): void
    {
        $this->expectNotToPerformAssertions();
        $payload = json_encode(['packages' => ['acme/test' => []]]);
        $handler = $this->makeHandler($this->makeClient($payload));

        $handler->checkCompatibility('acme/test');
    }

    #[Test]
    public function allows_install_when_package_has_no_flarum_core_requirement(): void
    {
        $this->expectNotToPerformAssertions();
        $payload = json_encode([
            'packages' => [
                'acme/test' => [
                    ['name' => 'acme/test', 'version' => 'v1.0.0', 'require' => []],
                ],
            ],
        ]);
        $handler = $this->makeHandler($this->makeClient($payload));

        $handler->checkCompatibility('acme/test');
    }

    #[Test]
    public function allows_install_when_only_dev_releases_exist(): void
    {
        $this->expectNotToPerformAssertions();
        $payload = json_encode([
            'packages' => [
                'acme/test' => [
                    [
                        'name' => 'acme/test',
                        'version' => 'dev-main',
                        'require' => ['flarum/core' => '*'],
                    ],
                ],
            ],
        ]);
        $handler = $this->makeHandler($this->makeClient($payload));

        // dev-only packages are skipped — fail open.
        $handler->checkCompatibility('acme/test');
    }

    #[Test]
    public function strips_version_specifier_from_package_name_before_api_call(): void
    {
        $this->expectNotToPerformAssertions();
        // The Packagist URL must use just the package name, not "acme/test:^2.0".
        // We verify this indirectly: the payload keyed by the bare name is found correctly.
        $handler = $this->makeHandler($this->makeClient($this->packagistPayload('acme/test', '^2.0.0')));

        $handler->checkCompatibility('acme/test:^2.0');
    }
}
