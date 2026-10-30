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

    #[Test]
    public function scripts_are_added_after_cores_each_in_a_function_of_its_own(): void
    {
        $this->extend(
            (new Extend\ServiceWorker())
                ->js($this->script("const name = 'first';"))
                ->js($this->script("const name = 'second';"))
        );

        $script = $this->serviceWorker();

        $this->assertStringContainsString("try {\n(function () {\nconst name = 'first';\n})();\n} catch (error) {\nconsole.error(error);\n}\n", $script);
        $this->assertStringContainsString("try {\n(function () {\nconst name = 'second';\n})();\n} catch (error) {\nconsole.error(error);\n}\n", $script);
        $this->assertLessThan(strpos($script, "'first'"), strpos($script, 'offlinePage({'));
        $this->assertLessThan(strpos($script, "'second'"), strpos($script, "'first'"));
    }

    #[Test]
    public function a_script_that_does_not_exist_is_left_out(): void
    {
        $this->extend((new Extend\ServiceWorker())->js('/nowhere/sw.js'));

        $this->assertStringNotContainsString('/nowhere/sw.js', $this->serviceWorker());
    }
}
