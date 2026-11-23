<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Discover\AbandonedList;
use PHPUnit\Framework\TestCase;

class AbandonedListTest extends TestCase
{
    public function test_every_shape_of_entry_is_read_and_junk_is_skipped(): void
    {
        $list = AbandonedList::fromMap([
            'Acme/Old' => ['replacement' => ' Acme/New '],
            'acme/gone' => [],
            'acme/self' => ['replacement' => 'ACME/self'],
            'acme/blank' => ['replacement' => ''],
            'acme/odd' => ['replacement' => ['not', 'a', 'name']],
            'acme/unnamed' => ['replacement' => 'see the README'],
            'acme/junk' => 'not an entry',
            0 => ['replacement' => 'acme/x'],
        ]);

        $this->assertSame('acme/new', $list->replacement('acme/old'));
        $this->assertSame('acme/new', $list->flag('ACME/OLD'));
        $this->assertTrue($list->flag('acme/gone'));
        $this->assertTrue($list->flag('acme/self'));
        $this->assertTrue($list->flag('acme/blank'));
        $this->assertTrue($list->flag('acme/odd'));
        $this->assertTrue($list->flag('acme/unnamed'));
        $this->assertFalse($list->has('acme/junk'));
        $this->assertFalse($list->flag('acme/fine'));
        $this->assertNull($list->replacement('acme/fine'));
    }
}
