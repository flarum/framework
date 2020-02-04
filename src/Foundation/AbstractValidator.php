<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Flarum\Foundation\Event\Validating;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Translation\TranslatorInterface;

abstract class AbstractValidator
{
    /**
     * @var array
     */
    protected $rules = [];

    /**
     * @var Factory
     */
    protected $validator;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param Factory $validator
     * @param Dispatcher $events
     * @param TranslatorInterface $translator
     */
    public function __construct(Factory $validator, Dispatcher $events, TranslatorInterface $translator)
    {
        $this->validator = $validator;
        $this->events = $events;
        $this->translator = $translator;
    }

    /**
     * Throw an exception if a model is not valid.
     *
     * @param array $attributes
     */
    public function assertValid(array $attributes)
    {
        $validator = $this->makeValidator($attributes);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * @return array
     */
    protected function getRules()
    {
        return $this->rules;
    }

    /**
     * @return array
     */
    protected function getMessages()
    {
        return [];
    }

    /**
     * Make a new validator instance for this model.
     *
     * @param array $attributes
     * @return \Illuminate\Validation\Validator
     */
    protected function makeValidator(array $attributes)
    {
        $rules = Arr::only($this->getRules(), array_keys($attributes));

        $validator = $this->validator->make($attributes, $rules, $this->getMessages());

        $this->events->dispatch(
            new Validating($this, $validator)
        );

        return $validator;
    }
}
