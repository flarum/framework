<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Flarum\Foundation\ContainerUtil;
use Illuminate\Contracts\Container\Container;

class Validator implements ExtenderInterface
{
    private $configurationCallbacks = [];
    private $validator;

    /**
     * @param string $validatorClass: The ::class attribute of the validator you are modifying.
     *                                The validator should inherit from \Flarum\Foundation\AbstractValidator.
     */
    public function __construct(string $validatorClass)
    {
        $this->validator = $validatorClass;
    }

    /**
     * Configure the validator. This is often used to adjust validation rules, but can be
     * used to make other changes to the validator as well.
     *
     * @param callable $callback
     *
     * The callback can be a closure or invokable class, and should accept:
     * - \Flarum\Foundation\AbstractValidator $flarumValidator: The Flarum validator wrapper
     * - \Illuminate\Validation\Validator $validator: The Laravel validator instance
     *
     * The callback should return void.
     *
     * @return self
     */
    public function configure($callback): self
    {
        $this->configurationCallbacks[] = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->resolving($this->validator, function ($validator, $container) {
            foreach ($this->configurationCallbacks as $callback) {
                $validator->addConfiguration(ContainerUtil::wrapCallback($callback, $container));
            }
        });
    }
}
