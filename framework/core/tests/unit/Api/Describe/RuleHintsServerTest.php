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

class RuleHintsServerTest extends TestCase
{
    #[Test]
    public function rule_names_read_string_forms()
    {
        $this->assertSame(['required', 'max', 'in'], RuleHints::ruleNames(['required', 'max:5', Rule::in(['a', 'b']), fn () => null]));
    }

    #[Test]
    public function rules_map_to_the_constraint_keys_they_produce()
    {
        $this->assertSame(['required'], RuleHints::constraintKeys('required_without'));
        $this->assertSame([], RuleHints::constraintKeys('required_array_keys'));
        $this->assertSame(['pattern'], RuleHints::constraintKeys('regex'));
        $this->assertSame(['maxLength', 'maximum', 'maxItems'], RuleHints::constraintKeys('max'));
        $this->assertSame(['format'], RuleHints::constraintKeys('email'));
        $this->assertSame(['enum', 'items'], RuleHints::constraintKeys('in'));
        $this->assertSame([], RuleHints::constraintKeys('unique'));
    }
}
