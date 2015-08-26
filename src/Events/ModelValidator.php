<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Events;

use Flarum\Core\Model;
use Illuminate\Validation\Validator;

/**
 * The `ModelValidator` event is called when a validator instance for a
 * model is being built. This event can be used to add custom rules/extensions
 * to the validator for when validation takes place.
 */
class ModelValidator
{
    /**
     * @var Model
     */
    public $model;

    /**
     * @var Validator
     */
    public $validator;

    /**
     * @param Model $model
     * @param Validator $validator
     */
    public function __construct(Model $model, Validator $validator)
    {
        $this->model = $model;
        $this->validator = $validator;
    }
}
