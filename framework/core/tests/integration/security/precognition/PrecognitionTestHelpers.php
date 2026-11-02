<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\security\precognition;

use Flarum\Api\PrecognitionThrottler;
use Illuminate\Contracts\Cache\Repository as Cache;
use Laminas\Diactoros\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Shared helpers for the precognition security tests.
 *
 * @mixin \Flarum\Testing\integration\TestCase
 */
trait PrecognitionTestHelpers
{
    protected function precognitive(ServerRequestInterface $request, ?string $only = null): ServerRequestInterface
    {
        $request = $request->withHeader('Precognition', 'true');

        return $only === null ? $request : $request->withHeader('Precognition-Validate-Only', $only);
    }

    /**
     * A JSON:API request, optionally from a given REMOTE_ADDR, as an actor or as a guest.
     */
    protected function jsonRequest(string $method, string $path, ?array $json = null, ?int $as = null, ?string $ip = null): ServerRequestInterface
    {
        if ($ip === null) {
            $request = $this->request($method, $path, array_filter([
                'json' => $json,
                'authenticatedAs' => $as,
            ], fn ($v) => $v !== null));
        } else {
            $request = new ServerRequest(['REMOTE_ADDR' => $ip], [], $path, $method);

            if ($json !== null) {
                $request = $this->requestWithJsonBody($request, $json);
            }

            if ($as !== null) {
                $request = $this->requestAsUser($request, $as);
            }
        }

        return $as === null ? $request->withAttribute('bypassCsrfToken', true) : $request;
    }

    protected function unlimitedPrecognition(): void
    {
        $this->app()->getContainer()->singleton('flarum.api.precognition_throttler', function ($container) {
            return new PrecognitionThrottler($container->make(Cache::class), PHP_INT_MAX, 60);
        });
    }

    protected function body(ResponseInterface $response): array
    {
        return json_decode((string) $response->getBody(), true) ?? [];
    }

    /**
     * A fingerprint of every table's content. Columns touched by authenticating a
     * request at all (token activity, last seen) are left out.
     *
     * @return array<string, string>
     */
    protected function databaseFingerprint(): array
    {
        $db = $this->database();
        $fingerprint = [];

        $prefix = $db->getTablePrefix();

        // Every table of this install, unprefixed, on any supported database.
        $tables = [];

        foreach ($db->getSchemaBuilder()->getTables() as $table) {
            if ($prefix === '' || str_starts_with($table['name'], $prefix)) {
                $tables[] = substr($table['name'], strlen($prefix));
            }
        }

        foreach ($tables as $table) {
            if (in_array($table, ['access_tokens', 'migrations'], true)) {
                $fingerprint[$table] = (string) $db->table($table)->count();
                continue;
            }

            $rows = $db->table($table)->get()->map(function ($row) use ($table) {
                $row = (array) $row;

                if ($table === 'users') {
                    unset($row['last_seen_at']);
                }

                return json_encode($row);
            })->all();

            // Row order is not guaranteed without a key every table shares.
            sort($rows);

            $fingerprint[$table] = md5(implode("\n", $rows));
        }

        return $fingerprint;
    }

    /**
     * Files under the test instance's public/ and storage/, except caches, sessions and logs.
     *
     * @return string[]
     */
    protected function fileFingerprint(): array
    {
        $root = rtrim((string) getenv('FLARUM_TEST_TMP_DIR_LOCAL') ?: (string) getenv('FLARUM_TEST_TMP_DIR'), '/');

        if ($root === '' || ! is_dir($root)) {
            return [];
        }

        $files = [];

        foreach (['public', 'storage'] as $dir) {
            if (! is_dir("$root/$dir")) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator("$root/$dir", \FilesystemIterator::SKIP_DOTS));

            foreach ($iterator as $file) {
                $path = substr($file->getPathname(), strlen($root) + 1);

                if (preg_match('#^storage/(cache|sessions|logs|formatter|views|locale|tmp)/#', $path)) {
                    continue;
                }

                $files[] = $path.':'.$file->getSize().':'.$file->getMTime();
            }
        }

        sort($files);

        return $files;
    }
}
