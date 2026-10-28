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
        $hints = RuleHints::parse([new \stdClass, fn () => true, 'nullable']);

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
}
