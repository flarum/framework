<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Api\Schema;

use Flarum\Api\Describe\FieldDescriber;
use Flarum\Api\Schema\Str;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\Test;

class FlarumFieldUiTest extends TestCase
{
    #[Test]
    public function typed_methods_write_ui_hints()
    {
        $field = Str::make('title')
            ->column(80)
            ->widget('textarea')
            ->placeholder('core.some.placeholder')
            ->help('Help text')
            ->group('core.some.group')
            ->priority(10)
            ->display('name')
            ->visibleWhen('if:type,developer')
            ->markRequired();

        $this->assertSame([
            'column' => 80,
            'widget' => 'textarea',
            'placeholder' => 'core.some.placeholder',
            'help' => 'Help text',
            'group' => 'core.some.group',
            'priority' => 10,
            'display' => 'name',
            'visibleWhen' => 'if:type,developer',
            'markRequired' => true,
        ], $field->getUi());
    }

    #[Test]
    public function column_hidden_offers_a_hidden_column()
    {
        $this->assertSame(['column' => true, 'hidden' => true], Str::make('a')->columnHidden()->getUi());
        $this->assertSame(['column' => 20, 'hidden' => true], Str::make('a')->columnHidden(20)->getUi());
    }

    #[Test]
    public function typed_methods_and_free_form_hints_merge()
    {
        $field = Str::make('a')->ui(['column' => true, 'custom' => 1])->column(false)->ui(['label' => 'A']);

        $this->assertSame(['column' => false, 'custom' => 1, 'label' => 'A'], $field->getUi());
    }

    #[Test]
    public function visible_when_conditions_are_normalised()
    {
        $this->assertSame([['rule' => 'if', 'fields' => ['type'], 'values' => ['developer', 'session']]], FieldDescriber::conditions('if:type,developer,session'));
        $this->assertSame([['rule' => 'without', 'fields' => ['token']]], FieldDescriber::conditions('required_without:token'));
        $this->assertSame([['rule' => 'withAll', 'fields' => ['a', 'b']]], FieldDescriber::conditions(['with_all:a,b']));
        $this->assertSame([['rule' => 'with', 'fields' => ['a']]], FieldDescriber::conditions(['rule' => 'with', 'fields' => ['a']]));
        $this->assertSame([['rule' => 'if', 'fields' => ['a'], 'values' => ['1', null]]], FieldDescriber::conditions([['rule' => 'if', 'fields' => ['a'], 'values' => [1, null]]]));
        $this->assertSame([], FieldDescriber::conditions([['nonsense' => true], 42]));
        $this->assertSame([], FieldDescriber::conditions(null));
    }
}
