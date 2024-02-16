<?php

namespace Flarum\Api\Resource\Concerns;

use Illuminate\Contracts\Validation\Factory;

trait ResolvesValidationFactory
{
    /**
     * Called by the JSON:API server package to resolve the validation factory.
     */
    public function validationFactory(): Factory
    {
        return resolve(Factory::class);
    }
}
