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
use Illuminate\Validation\Rules\ExcludeIf;
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
    public function url_maps_to_the_uri_format()
    {
        $this->assertSame(['format' => 'uri'], RuleHints::parse(['url']));
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

    public static function formats(): array
    {
        return [
            'uuid' => ['uuid', 'uuid'],
            'ulid' => ['ulid', 'ulid'],
            'ip' => ['ip', 'ip'],
            'ipv4' => ['ipv4', 'ipv4'],
            'ipv6' => ['ipv6', 'ipv6'],
            'hex_color' => ['hex_color', 'hex-color'],
            'email' => ['email:rfc,dns', 'email'],
        ];
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('formats')]
    public function format_rules_map_to_formats(string $rule, string $format)
    {
        $this->assertSame(['format' => $format], RuleHints::parse([$rule]));
    }

    #[Test]
    public function size_sets_both_bounds_per_kind()
    {
        $this->assertSame(['minLength' => 4, 'maxLength' => 4], RuleHints::parse(['size:4']));
        $this->assertSame(['minimum' => 4, 'maximum' => 4], RuleHints::parse(['size:4'], 'integer'));
        $this->assertSame(['minItems' => 2, 'maxItems' => 2], RuleHints::parse(['size:2'], 'array'));
    }

    #[Test]
    public function between_sets_both_bounds_per_kind()
    {
        $this->assertSame(['minLength' => 2, 'maxLength' => 5], RuleHints::parse(['between:2,5']));
        $this->assertSame(['minimum' => 0.5, 'maximum' => 5], RuleHints::parse(['between:0.5,5'], 'number'));
        $this->assertSame(['minItems' => 1, 'maxItems' => 3], RuleHints::parse(['between:1,3'], 'array'));
    }

    #[Test]
    public function a_numeric_rule_makes_size_rules_value_bounds_on_a_string_field()
    {
        $this->assertSame(['minimum' => 1, 'maximum' => 9], RuleHints::parse(['numeric', 'min:1', 'max:9']));
        $this->assertSame(['minimum' => 3, 'maximum' => 3], RuleHints::parse(['size:3', 'integer']));
    }

    #[Test]
    public function digits_map_to_patterns()
    {
        $this->assertSame(['pattern' => '^[0-9]{4}$'], RuleHints::parse(['digits:4']));
        $this->assertSame(['pattern' => '^[0-9]{2,6}$'], RuleHints::parse(['digits_between:2,6']));
    }

    #[Test]
    public function numeric_comparisons_map_to_bounds()
    {
        $this->assertSame(
            ['exclusiveMinimum' => 0, 'minimum' => 1, 'exclusiveMaximum' => 10, 'maximum' => 9.5],
            RuleHints::parse(['gt:0', 'gte:1', 'lt:10', 'lte:9.5'], 'number')
        );
    }

    #[Test]
    public function comparisons_on_a_string_are_whole_lengths()
    {
        $this->assertSame(['minLength' => 3, 'maxLength' => 9], RuleHints::parse(['gt:2', 'lt:10']));
    }

    #[Test]
    public function comparisons_against_another_field_are_ignored()
    {
        $this->assertSame([], RuleHints::parse(['gt:other', 'lte:attributes.max'], 'integer'));
    }

    #[Test]
    public function not_in_is_never_described_and_is_left_to_the_server()
    {
        $this->assertSame([], RuleHints::parse(['not_in:a,b']));
        $this->assertSame([], RuleHints::parse([Rule::notIn(['x'])]));
        $this->assertTrue(RuleHints::needsServer(['not_in:a,b']));
    }

    #[Test]
    public function the_server_only_marker_is_never_described_and_needs_the_server()
    {
        $this->assertSame([], RuleHints::parse([RuleHints::SERVER_ONLY]));
        $this->assertTrue(RuleHints::needsServer([RuleHints::SERVER_ONLY]));
        $this->assertSame(['always' => false, 'when' => [], 'undescribable' => false], RuleHints::requirements([RuleHints::SERVER_ONLY]));
    }

    #[Test]
    public function value_lists_are_recognised_in_every_form()
    {
        foreach (['in:a,b', 'required|not_in:x', 'enum:'.\stdClass::class, Rule::in(['a']), Rule::notIn(['a'])] as $rule) {
            $this->assertTrue(RuleHints::isValueList($rule), is_string($rule) ? $rule : get_class($rule));
        }

        foreach (['max:5', 'required', 'regex:/in:a/', fn () => 'in:a', null] as $rule) {
            $this->assertFalse(RuleHints::isValueList($rule));
        }
    }

    #[Test]
    public function in_on_an_array_constrains_its_items()
    {
        $this->assertSame(['items' => ['enum' => ['a', 'b']]], RuleHints::parse(['in:a,b'], 'array'));
    }

    #[Test]
    public function accepted_and_declined_are_hints()
    {
        $this->assertSame(['accepted' => true], RuleHints::parse(['accepted'], 'boolean'));
        $this->assertSame(['declined' => true], RuleHints::parse(['declined'], 'boolean'));
    }

    #[Test]
    public function distinct_and_multiple_of_map_to_their_keywords()
    {
        $this->assertSame(['uniqueItems' => true], RuleHints::parse(['distinct'], 'array'));
        $this->assertSame(['multipleOf' => 5], RuleHints::parse(['multiple_of:5'], 'integer'));
    }

    #[Test]
    public function in_rule_objects_are_read_through_their_string_form()
    {
        $this->assertSame(['enum' => ['a', 'b"c']], RuleHints::parse([Rule::in(['a', 'b"c'])]));
    }

    #[Test]
    public function a_backed_enum_rule_maps_to_its_case_values()
    {
        $this->assertSame(['enum' => ['r', 'g', 'b']], RuleHints::parse([Rule::enum(RuleHintsColour::class)]));
        $this->assertSame(['enum' => ['g']], RuleHints::parse([Rule::enum(RuleHintsColour::class)->only(RuleHintsColour::Green)]));
        $this->assertSame(['enum' => ['r', 'b']], RuleHints::parse([Rule::enum(RuleHintsColour::class)->except([RuleHintsColour::Green])]));
    }

    #[Test]
    public function a_pure_enum_rule_is_ignored()
    {
        $this->assertSame([], RuleHints::parse([Rule::enum(RuleHintsSuit::class)]));
    }

    #[Test]
    public function a_rule_object_whose_string_form_runs_a_closure_is_not_stringified()
    {
        $called = false;

        RuleHints::parse([Rule::requiredIf(function () use (&$called) {
            return $called = true;
        })]);

        $this->assertFalse($called);
    }

    #[Test]
    public function sometimes_with_required_is_filled()
    {
        $this->assertSame(['filled' => true], RuleHints::parse(['sometimes', 'required']));
        $this->assertSame(['always' => false, 'when' => [], 'undescribable' => false], RuleHints::requirements(['sometimes', 'required']));
    }

    #[Test]
    public function several_regexes_are_all_kept()
    {
        $this->assertSame(
            ['patterns' => [['pattern' => '^a', 'flags' => 'i'], ['pattern' => 'b$']]],
            RuleHints::parse(['regex:/^a/i', 'regex:/b$/'])
        );
    }

    #[Test]
    public function a_regex_and_digits_rule_are_both_kept()
    {
        $this->assertSame(
            ['patterns' => [['pattern' => '^1'], ['pattern' => '^[0-9]{3}$']]],
            RuleHints::parse(['regex:/^1/', 'digits:3'])
        );
    }

    #[Test]
    public function string_anchors_are_translated()
    {
        $this->assertSame(['pattern' => '^a+$', 'patternFlags' => 'u'], RuleHints::parse(['regex:/\Aa+\z/u']));
        // An escaped backslash before `A` is a literal backslash and `A`.
        $this->assertSame(['pattern' => '\\\\A'], RuleHints::parse(['regex:/\\\\A/']));
    }

    public static function pcreOnly(): array
    {
        return [
            '\Z' => ['/^a\Z/'],
            'possessive +' => ['/^a++$/'],
            'possessive *' => ['/^a*+$/'],
            'possessive ?' => ['/^a?+$/'],
            'atomic group' => ['/^(?>a|b)$/'],
            'inline modifier' => ['/(?i)abc/'],
            'scoped modifier' => ['/(?i:abc)/'],
            '\h' => ['/^\h+$/'],
            '\R' => ['/a\Rb/'],
            'posix class' => ['/[[:alpha:]]/'],
            '\A in a class' => ['/[\A]/'],
            '\A under m' => ['/\Aa$/m'],
            '\z under m' => ['/^a\z/m'],
            '\p without u' => ['/^\p{L}+$/'],
            '\pL shorthand' => ['/^\pL+$/u'],
            '\v' => ['/a\vb/'],
            '\N' => ['/a\Nb/'],
            '\e' => ['/a\eb/'],
            '\a' => ['/a\ab/'],
            '\x{..}' => ['/\x{41}/'],
            'identity escape of a letter' => ['/\y/'],
            'identity escape of a non-syntax char' => ['/a\@b/'],
            '\- outside a class' => ['/a\-b/'],
            'octal' => ['/\012/'],
            'backreference in a class' => ['/(a)[\1]/'],
        ];
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('pcreOnly')]
    public function a_regex_with_pcre_only_constructs_has_no_pattern(string $regex)
    {
        $this->assertSame([], RuleHints::parse(["regex:$regex"]));
    }

    #[Test]
    public function js_compatible_groups_and_quantifiers_are_kept()
    {
        $regex = '^(?:a|b)(?=c)(?!d)(?<=e)(?<!f)(?<name>g)[+*?]a+?b*c{2}$';

        $this->assertSame(['pattern' => $regex], RuleHints::parse(["regex:/$regex/"]));
    }

    #[Test]
    public function only_the_untranslatable_regex_is_dropped()
    {
        $this->assertSame(['pattern' => '^b'], RuleHints::parse(['regex:/^a++/', 'regex:/^b/']));
    }

    #[Test]
    public function prefixed_field_references_are_stripped()
    {
        $this->assertSame([
            ['rule' => 'with', 'fields' => ['a', 'b', 'c']],
            ['rule' => 'if', 'fields' => ['type'], 'values' => ['x']],
        ], RuleHints::requirements(['required_with:attributes.a,relationships.b,c', 'required_if:attributes.type,x'])['when']);
    }

    #[Test]
    public function a_null_compared_value_is_null()
    {
        $this->assertSame(
            [['rule' => 'if', 'fields' => ['type'], 'values' => [null, 'a']]],
            RuleHints::requirements(['required_if:type,null,a'])['when']
        );
    }

    #[Test]
    public function rule_when_leaves_the_decision_to_the_server()
    {
        $this->assertSame(
            ['always' => false, 'when' => [], 'undescribable' => true],
            RuleHints::requirements([Rule::when(fn () => true, ['required'])])
        );
    }

    #[Test]
    public function an_exclude_if_object_with_a_closure_counts_as_an_exclude_rule()
    {
        $this->assertSame(
            ['always' => false, 'when' => [], 'undescribable' => true],
            RuleHints::requirements([new ExcludeIf(fn () => true), 'required'])
        );
        $this->assertSame(
            ['always' => false, 'when' => [], 'undescribable' => false],
            RuleHints::requirements([new ExcludeIf(fn () => true), 'max:5'])
        );
    }

    #[Test]
    public function an_exclude_if_object_with_a_boolean_is_resolved()
    {
        $this->assertTrue(RuleHints::requirements([new ExcludeIf(false), 'required'])['always']);
        $this->assertTrue(RuleHints::requirements([new ExcludeIf(true), 'required'])['undescribable']);
    }
}

enum RuleHintsColour: string
{
    case Red = 'r';
    case Green = 'g';
    case Blue = 'b';
}

enum RuleHintsSuit
{
    case Hearts;

    #[Test]
    public function js_compatible_escapes_are_kept()
    {
        $regex = '^\\d\\w\\s\\.\\/\\(\\)[\\-\\]\\b]\\x41\\cJ\\0(a)\\1(?<n>b)\\k<n>\\bz$';

        $this->assertSame(['pattern' => $regex], RuleHints::parse(["regex:/$regex/"]));
    }

    #[Test]
    public function unicode_property_escapes_are_kept_with_the_u_flag()
    {
        $this->assertSame(['pattern' => '^\\p{L}\\P{Lu}$', 'patternFlags' => 'u'], RuleHints::parse(['regex:/^\\p{L}\\P{Lu}$/u']));
    }

    #[Test]
    public function anchors_are_kept_under_m_when_not_translated()
    {
        $this->assertSame(['pattern' => '^a$', 'patternFlags' => 'm'], RuleHints::parse(['regex:/^a$/m']));
    }

    #[Test]
    public function a_custom_rule_with_a_protected_condition_does_not_break_parsing()
    {
        $rule = new class implements \Illuminate\Contracts\Validation\ValidationRule {
            protected $condition = 'whatever';
            protected string $uninitialized;

            public function validate(string $attribute, mixed $value, \Closure $fail): void
            {
            }
        };

        $this->assertSame(['maxLength' => 5], RuleHints::parse([$rule, 'max:5']));
        $this->assertSame(['always' => true, 'when' => [], 'undescribable' => false], RuleHints::requirements([$rule, 'required']));
        $this->assertTrue(RuleHints::needsServer([$rule]));
    }

    #[Test]
    public function a_custom_rule_with_an_uninitialized_typed_condition_does_not_break_parsing()
    {
        $rule = new class implements \Illuminate\Contracts\Validation\ValidationRule {
            public bool $condition;

            public function validate(string $attribute, mixed $value, \Closure $fail): void
            {
            }
        };

        $this->assertSame(['always' => false, 'when' => [], 'undescribable' => false], RuleHints::requirements([$rule]));
    }
}
