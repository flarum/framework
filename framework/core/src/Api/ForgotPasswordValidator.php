<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api;

use Flarum\Foundation\AbstractValidator;

class ForgotPasswordValidator extends AbstractValidator
{
    protected array $rules = [
        'email' => ['required', 'email']
    ];
}
