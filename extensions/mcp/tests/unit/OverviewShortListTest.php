<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\unit;

use Flarum\Mcp\Overview\OverviewBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class OverviewShortListTest extends TestCase
{
    /**
     * @param list<string> $titles
     */
    protected function shortList(array $titles, int $room): string
    {
        $builder = (new ReflectionClass(OverviewBuilder::class))->newInstanceWithoutConstructor();

        return (fn () => $this->shortList($titles, $room))->call($builder);
    }

    #[Test]
    public function as_many_names_as_fit_then_how_many_more(): void
    {
        $titles = ['Tags', 'Likes', 'Flags', 'Approval', 'Sticky'];

        $list = $this->shortList($titles, 80);

        $this->assertLessThanOrEqual(80, mb_strlen($list));
        $this->assertStringStartsWith("\nEnabled extensions: Tags, Likes", $list);
        $this->assertMatchesRegularExpression('/, and \d more \(see flarum:\/\/forum\)\.\n$/', $list);
    }

    #[Test]
    public function only_the_count_when_no_name_fits(): void
    {
        $this->assertSame("\nEnabled extensions: 3 more (see flarum://forum).\n", $this->shortList(['Tags', 'Likes', 'Flags'], 10));
    }
}
