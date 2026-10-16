<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * 🚨 A static `key` on a static child has broken this extension's admin screen
 * three separate times, each one presenting as an endless loading spinner.
 *
 * Mithril requires that within one list of children, either every vnode has a
 * key or none does — and `null` (from a `cond ? x : null`) counts as one
 * WITHOUT. Mixing them throws during view; a throw during view renders nothing,
 * so whatever was on screen stays there, which is invariably the spinner. The
 * screen looks hung and the console holds the only evidence.
 *
 * Keys exist to track identity across reorders, which is `.map()` and nothing
 * else. A static child never needs one. So the rule is mechanical: no literal
 * `key="..."` anywhere. Then no children list can mix, because nothing static
 * is keyed, and this class of bug cannot recur.
 *
 * A PHP test guarding TypeScript is odd, but this suite is what runs on every
 * commit, and a rule nothing checks is a rule that lasts until the next time.
 */
class JsxKeysTest extends TestCase
{
    public function test_no_static_keys_in_any_component(): void
    {
        $root = dirname(__DIR__, 2).'/js/src';
        $offenders = [];

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            if (! in_array($file->getExtension(), ['tsx', 'ts'], true)) {
                continue;
            }

            foreach (file($file->getPathname()) as $n => $line) {
                // key="literal" — as opposed to key={expression}, which is the
                // .map() form and is the only legitimate one.
                if (preg_match('/\skey="[^"]*"/', $line)) {
                    $offenders[] = str_replace($root, 'js/src', $file->getPathname()).':'.($n + 1).' — '.trim($line);
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Static keys found. Mithril needs a key only inside .map(); a literal key on a static\n"
            ."child risks a children list where some vnodes are keyed and some are not, which\n"
            ."throws during view and leaves the last frame — a spinner — on screen:\n  "
            .implode("\n  ", $offenders)
        );
    }
}
