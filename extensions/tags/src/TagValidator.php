<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags;

use Flarum\Foundation\AbstractValidator;

class TagValidator extends AbstractValidator
{
    protected array $rules = [
        'name' => ['required'],
        'slug' => ['required', 'unique:tags', 'regex:/^[^\/\\ ]*$/i'],
        'is_hidden' => ['bool'],
        'description' => ['string', 'max:700'],
        'color' => ['hex_color'],
    ];
}
