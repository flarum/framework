<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Work;

/**
 * Runs a piece of somebody else's console code inside this PHP process, and
 * puts back everything it changed.
 *
 * 🚨 This process is not ours. Under PHP-FPM it is the request serving an admin
 * page, and in a queue worker it lives on to run every other job the forum has.
 * Composer and Symfony Console both treat the process they run in as their own:
 * Composer's ErrorHandler replaces the error handler and turns error_reporting
 * up to E_ALL, Symfony writes LINES, COLUMNS and SHELL_VERBOSITY into the
 * environment, Composer changes directory for --working-dir and resets the
 * default timezone. Any of those left behind changes how the rest of the forum
 * behaves — a warning that used to be logged becomes an exception that 500s a
 * page — in a place nobody would think to look.
 *
 * So everything that is touched is snapshotted first and restored in a
 * `finally`, including when the code inside throws.
 */
final class InProcess
{
    /** Environment variables the code inside is known to set. */
    private const WATCHED_ENV = [
        'COMPOSER_HOME', 'COMPOSER_ROOT_VERSION', 'COMPOSER_MEMORY_LIMIT', 'COMPOSER_NO_INTERACTION',
        'COMPOSER_CACHE_DIR', 'COMPOSER_NO_AUDIT', 'COMPOSER_ALLOW_SUPERUSER', 'HOME', 'LINES', 'COLUMNS', 'SHELL_VERBOSITY',
    ];

    /**
     * @template T
     * @param array<string,string> $env set for the duration, then restored
     * @param callable(array{memory:bool, time:bool}):T $work handed which limits could be lifted
     * @return T
     */
    public static function isolate(array $env, callable $work): mixed
    {
        $cwd = getcwd();
        $timezone = date_default_timezone_get();
        $reporting = error_reporting();
        $errorHandler = self::currentErrorHandler();
        $exceptionHandler = self::currentExceptionHandler();
        $memory = ini_get('memory_limit');
        $time = (int) ini_get('max_execution_time');

        $saved = [];
        foreach (array_unique([...self::WATCHED_ENV, ...array_keys($env)]) as $name) {
            $saved[$name] = [
                getenv($name),
                array_key_exists($name, $_SERVER) ? $_SERVER[$name] : null,
                array_key_exists($name, $_SERVER),
                array_key_exists($name, $_ENV) ? $_ENV[$name] : null,
                array_key_exists($name, $_ENV),
            ];
        }

        foreach ($env as $name => $value) {
            self::putEnv($name, $value);
        }

        $limits = self::raiseLimits();

        try {
            return $work($limits);
        } finally {
            if ($cwd !== false) {
                @chdir($cwd);
            }

            foreach ($saved as $name => [$value, $server, $hadServer, $envValue, $hadEnv]) {
                if (function_exists('putenv')) {
                    @putenv($value === false ? $name : "$name=$value");
                }

                if ($hadServer) {
                    $_SERVER[$name] = $server;
                } else {
                    unset($_SERVER[$name]);
                }

                if ($hadEnv) {
                    $_ENV[$name] = $envValue;
                } else {
                    unset($_ENV[$name]);
                }
            }

            self::restoreErrorHandler($errorHandler);
            self::restoreExceptionHandler($exceptionHandler);
            error_reporting($reporting);
            @date_default_timezone_set($timezone);

            if ($memory !== false) {
                @ini_set('memory_limit', $memory);
            }

            /*
             * 🚨 Put back the request's own limit. This restarts its clock, so
             * the rest of a request that has just spent a minute in Composer
             * gets its normal allowance rather than being killed at once.
             */
            if (function_exists('set_time_limit')) {
                @set_time_limit($time);
            }
        }
    }

    /**
     * Lift the memory and time limits for the duration, where the host allows.
     *
     * @return array{memory:bool, time:bool} which of the two could be lifted
     */
    public static function raiseLimits(): array
    {
        $memory = ini_get('memory_limit') === '-1' || @ini_set('memory_limit', '-1') !== false;
        $time = (int) ini_get('max_execution_time') === 0
            || (function_exists('set_time_limit') && @set_time_limit(0));

        return ['memory' => $memory, 'time' => $time];
    }

    /**
     * Whether the host would let the limits be lifted, asked without leaving
     * either changed. For the host panel, before anybody presses anything.
     *
     * @return array{memory:bool, time:bool, timeLimit:int}
     */
    public static function limits(): array
    {
        $memoryWas = ini_get('memory_limit');
        $memory = $memoryWas === '-1' || @ini_set('memory_limit', '-1') !== false;
        if ($memoryWas !== false && $memoryWas !== '-1') {
            @ini_set('memory_limit', $memoryWas);
        }

        $timeLimit = (int) ini_get('max_execution_time');
        // Setting the limit to its own value is how to ask; it only restarts
        // this request's clock.
        $time = $timeLimit === 0 || (function_exists('set_time_limit') && @set_time_limit($timeLimit));

        return ['memory' => $memory, 'time' => $time, 'timeLimit' => $timeLimit];
    }

    private static function putEnv(string $name, string $value): void
    {
        if (function_exists('putenv')) {
            @putenv("$name=$value");
        }

        // Composer reads $_SERVER first, so this works even where putenv is disabled.
        $_SERVER[$name] = $_ENV[$name] = $value;
    }

    private static function currentErrorHandler(): mixed
    {
        $current = set_error_handler(static fn () => false);
        restore_error_handler();

        return $current;
    }

    private static function currentExceptionHandler(): mixed
    {
        $current = set_exception_handler(static fn () => null);
        restore_exception_handler();

        return $current;
    }

    /**
     * 🚨 Pop rather than set. Composer PUSHES its handler (once in the
     * constructor path, again in doRun), and setting the old one on top would
     * leave Composer's underneath it, to resurface the next time anybody calls
     * restore_error_handler(). Bounded, and falls back to setting it outright.
     */
    private static function restoreErrorHandler(mixed $previous): void
    {
        for ($i = 0; $i < 16 && self::currentErrorHandler() !== $previous; $i++) {
            restore_error_handler();
        }

        if (self::currentErrorHandler() !== $previous) {
            set_error_handler($previous);
        }
    }

    private static function restoreExceptionHandler(mixed $previous): void
    {
        for ($i = 0; $i < 16 && self::currentExceptionHandler() !== $previous; $i++) {
            restore_exception_handler();
        }

        if (self::currentExceptionHandler() !== $previous) {
            set_exception_handler($previous);
        }
    }
}
