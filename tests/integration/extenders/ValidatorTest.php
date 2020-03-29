<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\User\UserValidator;
use Flarum\Group\GroupValidator;
use Flarum\Tests\integration\TestCase;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class ModelTest extends TestCase
{
    private function extendToRequireLongPassword() {
        $this->extend((new Extend\Validator(UserValidator::class))->configure(function ($validator) {
            $rules = $validator->getRules();
            $passwordRules = Arr::get($rules, 'password', []);
            if (count($passwordRules)) {
                $rules['password'] = array_map(function ($rule) {
                    if ($rule === 'min:8') {
                        return 'min:20';
                    }

                    return $rule;
                }, $passwordRules);

                $validator->setRules($rules);
            }
        }));
    }
    /**
     * @test
     */
    public function custom_validation_rule_does_not_exist_by_default()
    {
        $this->app()->getContainer()->make(UserValidator::class)->assertValid(['password' => 'simplePassword']);

        // If we have gotten this far, no validation exception has been thrown, so the test is succesful.
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function custom_validation_rule_exists_if_added()
    {
        $this->extendToRequireLongPassword();

        $this->expectException(ValidationException::class);

        $this->app()->getContainer()->make(UserValidator::class)->assertValid(['password' => 'simplePassword']);
    }

    /**
     * @test
     */
    public function custom_validation_rule_doesnt_affect_other_validators()
    {
        $this->extendToRequireLongPassword();

        $this->app()->getContainer()->make(GroupValidator::class)->assertValid(['password' => 'simplePassword']);

        // If we have gotten this far, no validation exception has been thrown, so the test is succesful.
        $this->assertTrue(true);
    }
}
