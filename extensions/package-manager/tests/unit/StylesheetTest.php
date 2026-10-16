<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Less_Parser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The stylesheets compile with the compiler Flarum actually uses.
 *
 * 🚨 less.php and less.js do not agree, and the disagreement is not academic.
 * `minmax(min(100%, 280px), 1fr)` builds happily under node and throws
 * "incompatible types" under less.php — LESS has a `min()` of its own and will
 * not compare a percentage with a pixel length. Flarum compiles every
 * extension's LESS into ONE bundle, so a stylesheet that will not build takes
 * the whole forum's CSS with it and the site serves an unstyled 500.
 *
 * That happened. The build was green, node said fine, and the site was down.
 */
class StylesheetTest extends TestCase
{
    #[DataProvider('stylesheets')]
    public function test_it_compiles_under_less_php(string $file): void
    {
        if (! class_exists(Less_Parser::class)) {
            $this->markTestSkipped('wikimedia/less.php is not installed here.');
        }

        $parser = new Less_Parser(['compress' => false]);

        try {
            $parser->parseFile(dirname(__DIR__, 2).'/less/'.$file);
            $css = $parser->getCss();
        } catch (\Throwable $e) {
            $this->fail("less/$file does not compile under less.php: ".$e->getMessage());
        }

        $this->assertNotSame('', trim($css), "less/$file compiled to nothing.");
    }

    public static function stylesheets(): array
    {
        return ['admin.less' => ['admin.less']];
    }
}
