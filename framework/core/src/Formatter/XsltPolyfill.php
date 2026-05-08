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
     * Locate the vendored xslt-polyfill bundle inside core's js/dist.
     *
     * The polyfill is copied here from node_modules at `yarn build` time
     * (see the copy-xslt-polyfill script in framework/core/js/package.json),
     * so it ships as part of the published flarum/core package — operators
     * never need to run yarn themselves.
     */
    public static function findSource(): ?string
    {
        $sourceDir = __DIR__.'/../../js/dist/xslt-polyfill';

        if (file_exists($sourceDir.'/xslt-polyfill.min.js')) {
            return $sourceDir;
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
