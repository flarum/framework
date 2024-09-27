<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Foundation\AbstractValidator;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;

class ValidatorTest extends TestCase
{
    private function extendToRequireLongPassword()
    {
        $this->extend((new Extend\Validator(CustomUserValidator::class))->configure(function ($flarumValidator, $validator) {
            $validator->setRules([
                'password' => [
                    'required',
                    'min:20'
                ]
            ] + $validator->getRules());
        }));
    }

    private function extendToRequireLongPasswordViaInvokableClass()
    {
        $this->extend((new Extend\Validator(CustomUserValidator::class))->configure(CustomValidatorClass::class));
    }

    #[Test]
    public function custom_validation_rule_does_not_exist_by_default()
    {
        $this->app()->getContainer()->make(CustomUserValidator::class)->assertValid(['password' => 'simplePassword']);

        // If we have gotten this far, no validation exception has been thrown, so the test is successful.
        $this->assertTrue(true);
    }

    #[Test]
    public function custom_validation_rule_exists_if_added()
    {
        $this->extendToRequireLongPassword();

        $this->expectException(ValidationException::class);

        $this->app()->getContainer()->make(CustomUserValidator::class)->assertValid(['password' => 'simplePassword']);
    }

    #[Test]
    public function custom_validation_rule_exists_if_added_via_invokable_class()
    {
        $this->extendToRequireLongPasswordViaInvokableClass();

        $this->expectException(ValidationException::class);

        $this->app()->getContainer()->make(CustomUserValidator::class)->assertValid(['password' => 'simplePassword']);
    }

    #[Test]
    public function custom_validation_rule_doesnt_affect_other_validators()
    {
        $this->extendToRequireLongPassword();

        $this->app()->getContainer()->make(CustomValidator::class)->assertValid(['password' => 'simplePassword']);

        // If we have gotten this far, no validation exception has been thrown, so the test is successful.
        $this->assertTrue(true);
    }
}

class CustomValidatorClass
{
    public function __invoke($flarumValidator, $validator)
    {
        $validator->setRules([
            'password' => [
                'required',
                'min:20'
            ]
        ] + $validator->getRules());
    }
}

class CustomUserValidator extends AbstractValidator
{
    protected ?User $user = null;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    protected function getRules(): array
    {
        $idSuffix = $this->user ? ','.$this->user->id : '';

        return [
            'username' => [
                'required',
                'regex:/^[a-z0-9_-]+$/i',
                'unique:users,username'.$idSuffix,
                'min:3',
                'max:30'
            ],
            'email' => [
                'required',
                'email:filter',
                'unique:users,email'.$idSuffix
            ],
            'password' => [
                'required',
                'min:8'
            ]
        ];
    }

    protected function getMessages(): array
    {
        return [
            'username.regex' => $this->translator->trans('core.api.invalid_username_message')
        ];
    }
}

class CustomValidator extends AbstractValidator
{
    protected array $rules = [
        'name_singular' => ['required'],
        'name_plural' => ['required']
    ];
}
