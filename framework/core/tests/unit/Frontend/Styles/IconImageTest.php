<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Frontend\Styles;

use Flarum\Testing\unit\TestCase;
use Less_Parser;
use PHPUnit\Framework\Attributes\Test;

class IconImageTest extends TestCase
{
    #[Test]
    public function svg_artwork_is_hidden_while_its_image_background_is_kept(): void
    {
        $parser = new Less_Parser();
        $parser->parseFile(__DIR__.'/../../../../less/common/IconImage.less');
        $css = $parser->getCss();

        // Font Awesome Kits may produce a path, or a group of duotone paths.
        // Hide their contents, whose visibility is inherited by nested paths.
        $this->assertMatchesRegularExpression('/svg\.IconImage\s*>\s*\*\s*\{\s*visibility:\s*hidden;\s*\}/', $css);

        preg_match('/(?:^|\n)\.IconImage\s*\{([^}]+)\}/', $css, $image);
        $this->assertArrayHasKey(1, $image);
        $this->assertStringContainsString('background:', $image[1]);
        $this->assertStringNotContainsString('visibility:', $image[1]);
    }

    #[Test]
    public function mention_images_keep_their_proportions_within_an_icon_box(): void
    {
        $parser = new Less_Parser();
        $parser->parseFile(__DIR__.'/../../../../less/common/IconImage.less');
        preg_match('/img\.IconImage\s*\{([^}]+)\}/', $parser->getCss(), $image);

        $this->assertArrayHasKey(1, $image);
        $this->assertMatchesRegularExpression('/(?:^|[;\s])width:\s*auto;/', $image[1]);
        $this->assertMatchesRegularExpression('/(?:^|[;\s])height:\s*auto;/', $image[1]);
        $this->assertMatchesRegularExpression('/max-width:\s*1\.25em;/', $image[1]);
        $this->assertMatchesRegularExpression('/max-height:\s*1em;/', $image[1]);
    }
}
