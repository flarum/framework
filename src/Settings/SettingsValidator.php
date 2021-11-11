<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Settings;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Validation\ValidationException;

class SettingsValidator
{
    /**
     * @var Factory
     */
    protected $validatorFactory;

    /**
     * @var array
     */
    protected $settings;

    public function __construct(Factory $validatorFactory, array $settings)
    {
        $this->validatorFactory = $validatorFactory;
        $this->settings = $settings;
    }

    public function validate()
    {
        $validator = $this->validatorFactory->make(
            $this->settings,
            array_map(function ($value) {
                return 'max:65000';
            }, $this->settings),
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
