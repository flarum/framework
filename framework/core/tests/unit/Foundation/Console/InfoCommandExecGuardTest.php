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
 * Both `exec()` call sites must therefore be preceded by a
 * `function_exists('exec')` guard (which returns false for a disabled function)
 * so the affected best-effort fields degrade to their existing fallbacks
 * instead of fataling.
 */
class InfoCommandExecGuardTest extends TestCase
{
    /**
     * Both methods that invoke exec() must guard the call with
     * function_exists('exec') within the same method body.
     *
     * @dataProvider execCallSites
     * @test
     */
    public function exec_call_site_is_guarded(string $method)
    {
        $body = $this->methodBody($method);

        // If the method no longer calls exec() at all, there is nothing to guard.
        if (strpos($body, 'exec(') === false) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->assertStringContainsString(
            "function_exists('exec')",
            $body,
            "$method() calls exec() without a function_exists('exec') guard, so it will fatal on hosts where exec is disabled."
        );
    }

    /**
     * @return array<array{string}>
     */
    public function execCallSites(): array
    {
        return [
            'detectWebServerPhpVersion' => ['detectWebServerPhpVersion'],
            'findPackageVersion' => ['findPackageVersion'],
        ];
    }

    private function methodBody(string $method): string
    {
        $source = file_get_contents((new \ReflectionClass(InfoCommand::class))->getFileName());

        $start = strpos($source, "function $method");
        $this->assertNotFalse($start, "Expected InfoCommand to define $method().");

        // A window generous enough to cover the method body.
        return substr($source, $start, 1200);
    }
}
