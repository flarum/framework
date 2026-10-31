<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ServiceWorkerTest extends TestCase
{
    /** @var string[] */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            @unlink($path);
        }

        parent::tearDown();
    }

    private function script(string $code): string
    {
        $path = tempnam(sys_get_temp_dir(), 'flarum-sw-').'.js';
        $this->tempFiles[] = $path;
        file_put_contents($path, $code);

        return $path;
    }

    private function serviceWorker(): string
    {
        $response = $this->send($this->request('GET', '/sw'));

        $this->assertEquals(200, $response->getStatusCode());

        return (string) $response->getBody();
    }

    /**
     * @return list<string> the addresses the service worker imports, in order
     */
    private function imports(string $script): array
    {
        preg_match_all('/importScripts\("([^"]+)"\)/', $script, $matches);

        return $matches[1];
    }

    #[Test]
    public function scripts_are_imported_after_cores_each_in_a_function_of_its_own(): void
    {
        $this->extend(
            (new Extend\ServiceWorker())
                ->js($first = $this->script("const name = 'first';"))
                ->js($this->script("const name = 'second';"))
        );

        $script = $this->serviceWorker();
        $imports = $this->imports($script);

        $this->assertCount(2, $imports);
        $this->assertLessThan(strpos($script, 'importScripts('), strpos($script, 'offlinePage({'));
        // Without the domain or the file's path on the server.
        $this->assertMatchesRegularExpression('#^/sw/[0-9a-f]{12}\?v=[0-9a-f]{8}$#', $imports[0]);
        $this->assertStringNotContainsString(basename($first), $script);

        foreach ([$imports[0] => 'first', $imports[1] => 'second'] as $url => $name) {
            $this->assertStringContainsString("try {\n  importScripts(\"$url\");\n} catch (error) {\n  console.error(error);\n}\n", $script);

            $response = $this->send($this->request('GET', parse_url($url, PHP_URL_PATH)));

            $this->assertEquals(200, $response->getStatusCode());
            $this->assertSame('text/javascript; charset=utf-8', $response->getHeaderLine('Content-Type'));
            $this->assertSame("(function () {\nconst name = '$name';\n})();\n", (string) $response->getBody());
        }
    }

    #[Test]
    public function a_changed_script_changes_the_service_worker_so_browsers_update_it(): void
    {
        $path = $this->script("const name = 'before';");
        $this->extend((new Extend\ServiceWorker())->js($path));

        $before = $this->serviceWorker();
        file_put_contents($path, "const name = 'after';");

        $this->assertNotSame($before, $this->serviceWorker());
    }

    #[Test]
    public function a_script_that_is_not_added_is_not_found(): void
    {
        $this->assertEquals(404, $this->send($this->request('GET', '/sw/0123456789ab'))->getStatusCode());
    }

    #[Test]
    public function a_script_that_does_not_exist_is_left_out(): void
    {
        $this->extend((new Extend\ServiceWorker())->js('/nowhere/sw.js'));

        $this->assertStringNotContainsString('/nowhere/sw.js', $this->serviceWorker());
    }

    #[Test]
    public function an_extension_s_script_keeps_the_service_worker_without_opening_as_an_app(): void
    {
        $this->setting('open_as_app', false);

        $this->extend((new Extend\ServiceWorker())->js($this->script("const name = 'push';")));

        $script = $this->serviceWorker();

        $this->assertStringContainsString('offlinePage({', $script);
        $this->assertCount(1, $this->imports($script));

        $forum = json_decode((string) $this->send($this->request('GET', '/api'))->getBody(), true);

        $this->assertTrue($forum['data']['attributes']['serviceWorker']);
    }
}
