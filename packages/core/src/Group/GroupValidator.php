<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Group;

use Flarum\Foundation\AbstractValidator;

class GroupValidator extends AbstractValidator
{
    protected $rules = [
        'name_singular' => ['required'],
        'name_plural' => ['required']
    ];
}
