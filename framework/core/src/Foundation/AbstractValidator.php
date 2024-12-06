<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Flarum\Locale\TranslatorInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

abstract class AbstractValidator
{
    /**
     * @var callable[]
     */
    protected array $configuration = [];

    protected array $rules = [];

    protected ?Validator $laravelValidator = null;

    protected bool $validateMissingKeys = false;

    public function __construct(
        protected Factory $validator,
        protected TranslatorInterface $translator
    ) {
    }

    public function addConfiguration(callable $callable): void
    {
        $this->configuration[] = $callable;
    }

    /**
     * Throw an exception if a model is not valid.
     *
     * @throws ValidationException
     */
    public function assertValid(array $attributes): void
    {
        $validator = $this->makeValidator($attributes);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Whether to validate missing keys or to only validate provided data keys.
     */
    public function validateMissingKeys(bool $validateMissingKeys = true): static
    {
        $this->validateMissingKeys = $validateMissingKeys;

        return $this;
    }

    public function prepare(array $attributes): static
    {
        $this->laravelValidator ??= $this->makeValidator($attributes);

        return $this;
    }

    public function validator(): Validator
    {
        return $this->laravelValidator;
    }

    protected function getRules(): array
    {
        return $this->rules;
    }

    protected function getActiveRules(array $attributes): array
    {
        $rules = $this->getRules();

        if ($this->validateMissingKeys) {
            return $rules;
        }

        return Collection::make($rules)
            ->filter(function (mixed $rule, string $key) use ($attributes) {
                foreach ($attributes as $attributeKey => $attributeValue) {
                    if ($attributeKey === $key || Str::startsWith($key, $attributeKey.'.')) {
                        return true;
                    }
                }

                return false;
            })
            ->all();
    }

    protected function getMessages(): array
    {
        return [];
    }

    protected function makeValidator(array $attributes): Validator
    {
        $rules = $this->getActiveRules($attributes);

        $validator = $this->validator->make($attributes, $rules, $this->getMessages());

        foreach ($this->configuration as $callable) {
            $callable($this, $validator);
        }

        return $validator;
    }
}
