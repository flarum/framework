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
use Illuminate\Contracts\Container\Container;

class Validator implements ExtenderInterface
{
    private $configurationCallbacks = [];
    private $validator;

    public function __construct($validator)
    {
        $this->validator = $validator;
    }

    public function configure($callback)
    {
        $this->configurationCallbacks[] = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->resolving($this->validator, function ($validator, $container) {
            foreach ($this->configurationCallbacks as $callback) {
                if (is_string($callback)) {
                    $callback = $container->make($callback);
                }

                $validator->addConfiguration($callback);
            }
        });
    }
}
