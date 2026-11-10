<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Work;

/**
 * Keep Composer's autoloader from requiring files the swap has just taken away.
 *
 * Composer requires every `autoload.files` entry on EVERY request, with no
 * check that the file exists. So the moment a swap moves away a package that
 * has one (a removal, or an update whose new version dropped that file), every
 * request dies in the autoloader before Flarum, or this updater, can run.
 * The Extension Manager's next step is a request, so the run can neither finish nor put
 * itself back. Found when removing irmmr/flarum-ext-rtl, whose files entry is
 * its copy of LessCompiler (ClaudiusH, 2026-10-19).
 *
 * The autoloader is rebuilt properly by the register step in Finishing up, and
 * by Composer during a restore. This only has to hold the gap between a swap
 * and that rebuild, so it does the least that can: drop the entries whose file
 * is gone, in the same request that moved it.
 */
class AutoloadFiles
{
    /** @return list<string> the files no longer required */
    public static function pruneMissing(string $vendorDir): array
    {
        $vendorDir = rtrim($vendorDir, '/');
        $pruned = [];

        foreach (['autoload_static.php', 'autoload_files.php'] as $name) {
            $file = $vendorDir.'/composer/'.$name;
            $php = @file_get_contents($file);

            if (! is_string($php)) {
                continue;
            }

            $kept = preg_replace_callback(
                // One entry per line: 'hash' => <base> . '/path.php',
                // `__DIR__ . '/../..'` and `$baseDir` are the project root; the others vendor/.
                '/^[ \t]*\'[0-9a-f]{32}\'[ \t]*=>[ \t]*(__DIR__[ \t]*\.[ \t]*\'\/\.\.(?:\/\.\.)?\'|\$vendorDir|\$baseDir)[ \t]*\.[ \t]*\'([^\']+\.php)\',[ \t]*\r?\n/m',
                function (array $m) use ($vendorDir, &$pruned): string {
                    $root = $m[1] === '$baseDir' || str_contains($m[1], '/../..');
                    $base = $root ? dirname($vendorDir) : $vendorDir;
                    $path = $base.$m[2];

                    if (is_file($path)) {
                        return $m[0];
                    }

                    $pruned[] = $path;

                    return '';
                },
                $php
            );

            if (is_string($kept) && $kept !== $php) {
                // Written whole and renamed in, so a request never reads half of it.
                $tmp = $file.'.mw-'.bin2hex(random_bytes(4));

                if (@file_put_contents($tmp, $kept) !== false && @rename($tmp, $file)) {
                    if (function_exists('opcache_invalidate')) {
                        @opcache_invalidate($file, true);
                    }
                } else {
                    @unlink($tmp);
                }
            }
        }

        return array_values(array_unique($pruned));
    }
}
