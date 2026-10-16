<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Work;

use Flarum\ExtensionManager\Lang;
use RuntimeException;

/**
 * Runs one command and waits for it, without a shell.
 *
 * 🚨 An ARRAY command, never a string. proc_open with an array bypasses the
 * shell entirely, so a path containing a space is just a path — no quoting to
 * get right, and no way for anything to be re-parsed as a command.
 *
 * The version this replaced built a string with escapeshellcmd(), which escapes
 * shell metacharacters and does NOT quote spaces. On a machine whose PHP lives
 * under "Application Support" the command split at the space, and the error was
 * `sh: /Users/x/Library/Application: No such file or directory` — which names
 * neither the command that failed nor the reason.
 *
 * 🚨 Both pipes are drained as it runs. Reading stdout to completion before
 * touching stderr deadlocks the moment a command writes more to stderr than the
 * pipe buffer holds — rare enough to pass every test and then hang on somebody
 * else's host.
 */
class Process
{
    /**
     * @param list<string> $command
     * @param array<string,string> $env
     * @return array{code:int, output:string}
     */
    public static function run(array $command, string $cwd, array $env = [], int $timeout = 600): array
    {
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = @proc_open($command, $descriptors, $pipes, $cwd, $env ?: null);

        if (! is_resource($process)) {
            throw new RuntimeException(Lang::trans('process.start_failed', ['command' => $command[0] ?? Lang::trans('process.the_command')]));
        }

        foreach ($pipes as $pipe) {
            stream_set_blocking($pipe, false);
        }

        // stderr folded into the output on purpose: most tools say what matters
        // there, and somebody reading a failure wants all of it, in order.
        $output = '';
        $deadline = time() + $timeout;

        while (true) {
            $status = proc_get_status($process);
            $output .= (string) stream_get_contents($pipes[1]);
            $output .= (string) stream_get_contents($pipes[2]);

            if (! $status['running']) {
                break;
            }

            if (time() > $deadline) {
                proc_terminate($process, 9);

                throw new RuntimeException(Lang::trans('process.timeout', ['command' => $command[0] ?? Lang::trans('process.the_command_start'), 'seconds' => (string) $timeout]));
            }

            usleep(50000);
        }

        foreach ($pipes as $pipe) {
            fclose($pipe);
        }

        return ['code' => proc_close($process), 'output' => trim($output)];
    }
}
