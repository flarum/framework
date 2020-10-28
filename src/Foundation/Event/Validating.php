<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\Event;

use Flarum\Foundation\AbstractValidator;
use Illuminate\Validation\Validator;

/**
 * @deprecated in Beta 15, remove in beta 16. Use the Validator extender instead.
 * The `Validating` event is called when a validator instance for a
 * model is being built. This event can be used to add custom rules/extensions
 * to the validator for when validation takes place.
 */
class Validating
{
    /**
     * @var \Flarum\Foundation\AbstractValidator
     */
    public $type;

    /**
     * @var Validator
     */
    public $validator;

    /**
     * @param AbstractValidator $type
     * @param Validator $validator
     */
    public function __construct(AbstractValidator $type, Validator $validator)
    {
        $this->type = $type;
        $this->validator = $validator;
    }
}
