<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Formatter;

class XsltPolyfill
{
    /**
     * Locate the xslt-polyfill package directory in node_modules.
     *
     * Per-package install:  framework/core/js/node_modules/xslt-polyfill
     * Monorepo hoist:       node_modules/xslt-polyfill (at the repo root)
     */
    public static function findSource(): ?string
    {
        $candidates = [
            __DIR__.'/../../js/node_modules/xslt-polyfill',
            __DIR__.'/../../../../node_modules/xslt-polyfill',
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate.'/xslt-polyfill.min.js')) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Read the polyfill version from its package.json, used as a cache-bust
     * query string on the published URL so browsers pick up new versions
     * without waiting for heuristic revalidation.
     */
    public static function version(): ?string
    {
        $sourceDir = self::findSource();
        if ($sourceDir === null) {
            return null;
        }

        $packageJson = $sourceDir.'/package.json';
        if (! file_exists($packageJson)) {
            return null;
        }

        $data = json_decode(file_get_contents($packageJson), true);

        return is_array($data) && isset($data['version']) && is_string($data['version'])
            ? $data['version']
            : null;
    }
}
