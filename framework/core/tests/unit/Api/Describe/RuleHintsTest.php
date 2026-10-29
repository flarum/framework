<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Api\Describe;

use Flarum\Api\Describe\RuleHints;
use Flarum\Testing\unit\TestCase;
use Illuminate\Validation\Rule;
use PHPUnit\Framework\Attributes\Test;

class RuleHintsTest extends TestCase
{
    #[Test]
    public function maps_string_rules_to_hints()
    {
        $hints = RuleHints::parse([
            'required',
            'email:filter',
            'max:30',
            'min:3',
            'in:a,b',
            'regex:/^(?![0-9]*$)[a-z0-9_-]+$/i',
        ]);

        $this->assertSame([
            'required' => true,
            'format' => 'email',
            'maxLength' => 30,
            'minLength' => 3,
            'enum' => ['a', 'b'],
            'pattern' => '^(?![0-9]*$)[a-z0-9_-]+$',
            'patternFlags' => 'i',
        ], $hints);
    }

    #[Test]
    public function min_and_max_map_to_minimum_and_maximum_for_integers()
    {
        $this->assertSame(['minimum' => 1, 'maximum' => 9], RuleHints::parse(['min:1', 'max:9'], 'integer'));
    }

    #[Test]
    public function min_and_max_map_to_minimum_and_maximum_for_numbers()
    {
        $this->assertSame(['minimum' => 0.5, 'maximum' => 10], RuleHints::parse(['min:0.5', 'max:10'], 'number'));
    }

    #[Test]
    public function min_and_max_map_to_item_counts_for_arrays()
    {
        $this->assertSame(['minItems' => 1, 'maxItems' => 3], RuleHints::parse(['min:1', 'max:3'], 'array'));
    }

    #[Test]
    public function min_and_max_are_ignored_for_kinds_without_a_length()
    {
        $this->assertSame([], RuleHints::parse(['min:1', 'max:3'], 'boolean'));
    }

    #[Test]
    public function url_maps_to_the_url_format()
    {
        $this->assertSame(['format' => 'url'], RuleHints::parse(['url']));
    }

    #[Test]
    public function only_the_exact_required_rule_is_required()
    {
        $this->assertSame([], RuleHints::parse(['required_with:foo', 'required_if:a,b', 'required_without:bar']));
    }

    #[Test]
    public function regex_flags_are_mapped_to_pattern_flags()
    {
        $this->assertSame(['pattern' => '^a+$', 'patternFlags' => 'i'], RuleHints::parse(['regex:/^a+$/i']));
    }

    #[Test]
    public function all_js_compatible_regex_flags_are_kept()
    {
        $this->assertSame(['pattern' => '^a+$', 'patternFlags' => 'imsu'], RuleHints::parse(['regex:/^a+$/imsu']));
    }

    #[Test]
    public function a_regex_with_a_flag_js_cannot_use_has_no_pattern()
    {
        $this->assertSame([], RuleHints::parse(['regex:/^a+$/x']));
        $this->assertSame([], RuleHints::parse(['regex:/^a+$/iU']));
        $this->assertSame([], RuleHints::parse(['regex:/^a+$/D']));
    }

    #[Test]
    public function a_regex_with_a_non_slash_delimiter_is_stripped()
    {
        $this->assertSame(['pattern' => '^a$'], RuleHints::parse(['regex:#^a$#']));
        $this->assertSame(['pattern' => '^a/b$', 'patternFlags' => 'i'], RuleHints::parse(['regex:~^a/b$~i']));
    }

    #[Test]
    public function a_regex_with_bracket_delimiters_is_stripped()
    {
        $this->assertSame(['pattern' => '^a$'], RuleHints::parse(['regex:{^a$}']));
        $this->assertSame(['pattern' => '^(a)$', 'patternFlags' => 'i'], RuleHints::parse(['regex:(^(a)$)i']));
    }

    #[Test]
    public function a_malformed_regex_has_no_pattern()
    {
        $this->assertSame([], RuleHints::parse(['regex:/^a']));
        $this->assertSame([], RuleHints::parse(['regex:a']));
    }

    #[Test]
    public function ignores_rule_objects_and_closures()
    {
        $hints = RuleHints::parse([new \stdClass, fn () => true]);

        $this->assertSame([], $hints);
    }

    #[Test]
    public function strips_quotes_from_in_values()
    {
        $this->assertSame(['enum' => ['a', 'b']], RuleHints::parse(['in:"a","b"']));
    }

    #[Test]
    public function accepts_pipe_delimited_strings()
    {
        $this->assertSame(['required' => true, 'maxLength' => 5], RuleHints::parse('required|max:5'));
    }

    #[Test]
    public function nullable_filled_and_present_are_hints()
    {
        $this->assertSame(['nullable' => true, 'filled' => true, 'present' => true], RuleHints::parse(['nullable', 'filled', 'present']));
    }

    #[Test]
    public function the_plain_required_rule_is_always_required()
    {
        $this->assertSame(['always' => true, 'when' => [], 'undescribable' => false], RuleHints::requirements(['required', 'max:5']));
        $this->assertSame(['always' => true, 'when' => [], 'undescribable' => false], RuleHints::requirements('required|max:5'));
    }

    #[Test]
    public function no_required_rule_is_not_required()
    {
        $this->assertSame(['always' => false, 'when' => [], 'undescribable' => false], RuleHints::requirements(['max:5', 'nullable', 'required_array_keys:a']));
    }

    public static function conditions(): array
    {
        return [
            'with' => ['required_with:a,b', ['rule' => 'with', 'fields' => ['a', 'b']]],
            'withAll' => ['required_with_all:a,b', ['rule' => 'withAll', 'fields' => ['a', 'b']]],
            'without' => ['required_without:a', ['rule' => 'without', 'fields' => ['a']]],
            'withoutAll' => ['required_without_all:a,b', ['rule' => 'withoutAll', 'fields' => ['a', 'b']]],
            'if' => ['required_if:type,a', ['rule' => 'if', 'fields' => ['type'], 'values' => ['a']]],
            'if with commas' => ['required_if:type,a,b', ['rule' => 'if', 'fields' => ['type'], 'values' => ['a', 'b']]],
            'unless' => ['required_unless:type,a,b', ['rule' => 'unless', 'fields' => ['type'], 'values' => ['a', 'b']]],
            'ifAccepted' => ['required_if_accepted:terms', ['rule' => 'ifAccepted', 'fields' => ['terms']]],
            'ifDeclined' => ['required_if_declined:terms', ['rule' => 'ifDeclined', 'fields' => ['terms']]],
        ];
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('conditions')]
    public function each_conditional_required_rule_is_a_condition(string $rule, array $condition)
    {
        $this->assertSame(['always' => false, 'when' => [$condition], 'undescribable' => false], RuleHints::requirements([$rule]));
    }

    #[Test]
    public function several_conditions_are_kept_in_order()
    {
        $this->assertSame([
            ['rule' => 'without', 'fields' => ['token']],
            ['rule' => 'if', 'fields' => ['type'], 'values' => ['a', 'b']],
        ], RuleHints::requirements(['required_without:token', 'required_if:type,a,b'])['when']);
    }

    #[Test]
    public function quoted_values_are_unquoted()
    {
        $this->assertSame(
            [['rule' => 'if', 'fields' => ['type'], 'values' => ['a,b', 'c']]],
            RuleHints::requirements(['required_if:type,"a,b",c'])['when']
        );
    }

    #[Test]
    public function the_plain_required_rule_makes_conditions_moot()
    {
        $this->assertSame(['always' => true, 'when' => [], 'undescribable' => false], RuleHints::requirements(['required_with:a', 'required']));
    }

    #[Test]
    public function a_required_if_rule_object_with_a_boolean_is_resolved()
    {
        $this->assertTrue(RuleHints::requirements([Rule::requiredIf(true)])['always']);
        $this->assertSame(['always' => false, 'when' => [], 'undescribable' => false], RuleHints::requirements([Rule::requiredIf(false)]));
    }

    public static function undescribable(): array
    {
        return [
            'requiredIf closure' => [[Rule::requiredIf(fn () => true)]],
            'implicit rule object' => [[new class implements \Illuminate\Contracts\Validation\ImplicitRule {
                public function passes($attribute, $value)
                {
                    return true;
                }

                public function message()
                {
                    return '';
                }
            }]],
            'unknown required rule' => [['required_something:a']],
            'required_if without values' => [['required_if:type']],
            'required with exclude' => [['exclude_if:type,a', 'required']],
            'condition with exclude' => [['required_with:a', 'exclude_without:b']],
        ];
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('undescribable')]
    public function a_required_rule_that_cannot_be_described_is_marked(array $rules)
    {
        $requirements = RuleHints::requirements($rules);

        $this->assertTrue($requirements['undescribable']);
        $this->assertFalse($requirements['always']);
        $this->assertSame([], $requirements['when']);
    }

    #[Test]
    public function an_exclude_rule_alone_is_not_a_requirement()
    {
        $this->assertSame(['always' => false, 'when' => [], 'undescribable' => false], RuleHints::requirements(['exclude_if:type,a', 'max:5']));
    }
}
