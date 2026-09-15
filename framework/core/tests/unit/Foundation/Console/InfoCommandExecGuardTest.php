<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Foundation\Console;

use Flarum\Foundation\Console\InfoCommand;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;

/**
 * `InfoCommand` calls the global `exec()` from within the
 * `Flarum\Foundation\Console` namespace. When `exec` is listed in the host's
 * `disable_functions` (common on shared hosting), the disabled function is
 * removed from PHP's function table, the unqualified call resolves to neither a
 * local nor a global function, and PHP throws
 * `Call to undefined function Flarum\Foundation\Console\exec()`. That exits
 * `php flarum info` with 255 and — because `SystemInfoResource` runs the
 * command in-process — takes down the admin System Info page.
 *
 * Both call sites are best-effort, so with `exec` unavailable they must degrade
 * to their existing fallbacks rather than fatal.
 *
 * `disable_functions` can only be set at PHP startup, so these run the code in a
 * subprocess with `-d disable_functions=exec`.
 */
class InfoCommandExecGuardTest extends TestCase
{
    /**
     * `findPackageVersion()` reaches `exec()` whenever the package is a git
     * checkout, which a test can arrange, so this exercises the real method.
     */
    #[Test]
    public function find_package_version_falls_back_when_exec_is_disabled(): void
    {
        $result = $this->runInSubprocess('findPackageVersion', ['-d', 'disable_functions=exec']);

        $this->assertSame(
            0,
            $result['status'],
            "InfoCommand::findPackageVersion() fataled with exec disabled, so `flarum info` and the ".
            "admin System Info page break on hosts that disable exec. Output:\n".$result['output']
        );

        $this->assertStringContainsString(
            "RESULT='fallback-version'",
            $result['output'],
            'findPackageVersion() should fall back to the known version when exec is unavailable.'
        );
    }

    /**
     * Guards against a false pass above: with `exec` available the same harness
     * must actually reach `exec()` and resolve the commit hash. If this fails,
     * the precondition is not being met and the test above proves nothing.
     */
    #[Test]
    public function find_package_version_reaches_exec_when_it_is_available(): void
    {
        $result = $this->runInSubprocess('findPackageVersion');

        $this->assertSame(0, $result['status'], $result['output']);

        $this->assertMatchesRegularExpression(
            "/RESULT='fallback-version \([0-9a-f]{40}\)'/",
            $result['output'],
            'findPackageVersion() never reached exec(), so the test above would pass for the wrong reason.'
        );
    }

    /**
     * `detectWebServerPhpVersion()` only reaches `exec()` when one of its
     * hardcoded absolute binary paths (`/usr/bin/php` and friends) exists —
     * something a test cannot create, and which does not hold on macOS or in
     * every CI image. So this asserts the outcome that matters regardless: with
     * `exec` disabled the method returns null instead of fataling.
     *
     * On a host where a candidate binary *is* present (Linux, containers), this
     * exercises the guard for real; where none is, the method short-circuits
     * earlier and the assertion still holds. `findPackageVersion` above covers
     * the guard's behaviour under a precondition a test can actually satisfy.
     */
    #[Test]
    public function detect_web_server_php_version_returns_null_when_exec_is_disabled(): void
    {
        $result = $this->runInSubprocess('detectWebServerPhpVersion', ['-d', 'disable_functions=exec']);

        $this->assertSame(
            0,
            $result['status'],
            "InfoCommand::detectWebServerPhpVersion() fataled with exec disabled, so `flarum info` and ".
            "the admin System Info page break on hosts that disable exec. Output:\n".$result['output']
        );

        $this->assertStringContainsString(
            'RESULT=NULL',
            $result['output'],
            'detectWebServerPhpVersion() should return null when exec is unavailable.'
        );
    }

    /**
     * @param string[] $flags
     *
     * @return array{status: int, output: string}
     */
    private function runInSubprocess(string $method, array $flags = []): array
    {
        $script = $this->harnessScript();

        $command = array_merge([PHP_BINARY], $flags, [$script, $method]);

        $process = proc_open(
            $command,
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );

        $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_close($process);

        @unlink($script);

        return ['status' => $status, 'output' => $output];
    }

    /**
     * Writes a standalone script that invokes one private InfoCommand method
     * and prints RESULT=<var_export>. For findPackageVersion() it first creates
     * a real git checkout so the method gets as far as exec().
     */
    private function harnessScript(): string
    {
        $autoload = $this->locateAutoloader();
        $script = tempnam(sys_get_temp_dir(), 'flarum-exec-guard').'.php';

        file_put_contents($script, <<<PHP
            <?php

            require '$autoload';

            \$method = \$argv[1];
            \$rc = new ReflectionClass(\Flarum\Foundation\Console\InfoCommand::class);
            \$command = \$rc->newInstanceWithoutConstructor();
            \$args = [];

            if (\$method === 'findPackageVersion') {
                \$path = sys_get_temp_dir().'/flarum-exec-guard-repo-'.getmypid();
                @mkdir(\$path);

                // A real repository with one commit, so `git rev-parse HEAD`
                // succeeds and the method returns "<fallback> (<sha>)".
                \$quoted = escapeshellarg(\$path);
                shell_exec("git init -q \$quoted 2>&1");
                shell_exec("git -C \$quoted -c user.email=test@flarum.org -c user.name=Flarum commit -q --allow-empty -m test 2>&1");

                \$args = [\$path, 'fallback-version'];
            }

            \$result = \$rc->getMethod(\$method)->invokeArgs(\$command, \$args);

            echo 'RESULT='.var_export(\$result, true)."\n";
            PHP);

        return $script;
    }

    private function locateAutoloader(): string
    {
        $candidates = [
            __DIR__.'/../../../../vendor/autoload.php',
            __DIR__.'/../../../../../../../vendor/autoload.php',
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return realpath($candidate);
            }
        }

        $this->markTestSkipped('Could not locate the Composer autoloader.');
    }
}
