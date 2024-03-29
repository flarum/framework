<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion;

use Flarum\Foundation\AbstractValidator;

class DiscussionValidator extends AbstractValidator
{
    protected array $rules = [
        'title' => [
            'required',
            'min:3',
            'max:80'
        ]
    ];
}
