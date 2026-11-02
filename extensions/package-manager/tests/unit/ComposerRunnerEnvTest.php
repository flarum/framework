<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Tests\unit\EnglishTranslator;
use Flarum\ExtensionManager\Work\ComposerRunner;
use PHPUnit\Framework\TestCase;

/**
 * The Composer subprocess gets a fixed environment, plus the proxy and
 * certificate settings a host needs to reach the internet at all.
 */
class ComposerRunnerEnvTest extends TestCase
{
    private const NAMES = ['HTTPS_PROXY', 'https_proxy', 'NO_PROXY', 'SSL_CERT_FILE', 'SSL_CERT_DIR', 'CURL_CA_BUNDLE', 'COMPOSER_CAFILE', 'HTTP_PROXY'];

    private string $dir;

    /** @var array<string,string|false> */
    private array $saved = [];

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-env-'.bin2hex(random_bytes(4));
        mkdir($this->dir);

        // A "composer" that prints the environment it was given.
        file_put_contents($this->dir.'/composer', '<?php echo json_encode(getenv());');

        foreach ([...self::NAMES, 'UNRELATED_SETTING'] as $name) {
            $this->saved[$name] = getenv($name);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->saved as $name => $value) {
            putenv($value === false ? $name : "$name=$value");
        }

        exec('rm -rf '.escapeshellarg($this->dir));
    }

    /** @return array<string,string> */
    private function childEnv(): array
    {
        $runner = new ComposerRunner(EnglishTranslator::make(), $this->dir, $this->dir.'/composer', $this->dir.'/home', PHP_BINARY);

        return (array) json_decode($runner->run([])['output'], true);
    }

    public function test_proxy_and_certificate_settings_reach_composer(): void
    {
        putenv('HTTPS_PROXY=http://proxy.example.test:3128');
        putenv('https_proxy=http://proxy.example.test:3128');
        putenv('NO_PROXY=localhost,.internal');
        putenv('SSL_CERT_FILE=/etc/ssl/custom.pem');
        putenv('SSL_CERT_DIR=/etc/ssl/certs');
        putenv('CURL_CA_BUNDLE=/etc/ssl/bundle.pem');
        putenv('COMPOSER_CAFILE=/etc/ssl/composer.pem');
        putenv('UNRELATED_SETTING=1');

        $env = $this->childEnv();

        $this->assertSame('http://proxy.example.test:3128', $env['HTTPS_PROXY'] ?? null);
        $this->assertSame('http://proxy.example.test:3128', $env['https_proxy'] ?? null);
        $this->assertSame('localhost,.internal', $env['NO_PROXY'] ?? null);
        $this->assertSame('/etc/ssl/custom.pem', $env['SSL_CERT_FILE'] ?? null);
        $this->assertSame('/etc/ssl/certs', $env['SSL_CERT_DIR'] ?? null);
        $this->assertSame('/etc/ssl/bundle.pem', $env['CURL_CA_BUNDLE'] ?? null);
        $this->assertSame('/etc/ssl/composer.pem', $env['COMPOSER_CAFILE'] ?? null);
        $this->assertArrayNotHasKey('UNRELATED_SETTING', $env, 'everything else is still left behind');
        $this->assertSame('-1', $env['COMPOSER_MEMORY_LIMIT'] ?? null);
    }

    /** Only when set: an empty or missing one is not passed as an empty value. */
    public function test_a_setting_that_is_not_there_is_not_passed(): void
    {
        foreach (self::NAMES as $name) {
            putenv($name);
        }

        putenv('HTTP_PROXY=');

        $env = $this->childEnv();

        foreach (self::NAMES as $name) {
            $this->assertArrayNotHasKey($name, $env);
        }
    }
}
