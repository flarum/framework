<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Flarum\Locale\TranslatorInterface;
use Illuminate\Support\Arr;
use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

abstract class AbstractValidator
{
    /**
     * @var callable[]
     */
    protected array $configuration = [];

    /**
     * @var array
     */
    protected array $rules = [];

    public function __construct(
        protected Factory $validator,
        protected TranslatorInterface $translator
    ) {
    }

    public function addConfiguration($callable): void
    {
        $this->configuration[] = $callable;
    }

    /**
     * Throw an exception if a model is not valid.
     */
    public function assertValid(array $attributes): void
    {
        $validator = $this->makeValidator($attributes);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    protected function getRules(): array
    {
        return $this->rules;
    }

    protected function getMessages(): array
    {
        return [];
    }

    protected function makeValidator(array $attributes): Validator
    {
        $rules = Arr::only($this->getRules(), array_keys($attributes));

        $validator = $this->validator->make($attributes, $rules, $this->getMessages());

        foreach ($this->configuration as $callable) {
            $callable($this, $validator);
        }

        return $validator;
    }
}
