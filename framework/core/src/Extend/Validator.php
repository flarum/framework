<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Flarum\Foundation\AbstractValidator;
use Flarum\Foundation\ContainerUtil;
use Illuminate\Contracts\Container\Container;

class Validator implements ExtenderInterface
{
    private array $configurationCallbacks = [];

    /**
     * @param class-string<AbstractValidator> $validatorClass: The ::class attribute of the validator you are modifying.
     *                                The validator should inherit from \Flarum\Foundation\AbstractValidator.
     */
    public function __construct(
        private readonly string $validatorClass
    ) {
    }

    /**
     * Configure the validator. This is often used to adjust validation rules, but can be
     * used to make other changes to the validator as well.
     *
     * @param (callable(AbstractValidator $flarumValidator, \Illuminate\Validation\Validator $validator): void)|class-string $callback
     *
     * The callback can be a closure or invokable class, and should accept:
     * - \Flarum\Foundation\AbstractValidator $flarumValidator: The Flarum validator wrapper
     * - \Illuminate\Validation\Validator $validator: The Laravel validator instance
     *
     * The callback should return void.
     *
     * @return self
     */
    public function configure(callable|string $callback): self
    {
        $this->configurationCallbacks[] = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null): void
    {
        $container->resolving($this->validatorClass, function ($validator, $container) {
            foreach ($this->configurationCallbacks as $callback) {
                $validator->addConfiguration(ContainerUtil::wrapCallback($callback, $container));
            }
        });
    }
}
