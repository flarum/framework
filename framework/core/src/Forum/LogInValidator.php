<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum;

use Flarum\Foundation\AbstractValidator;

class LogInValidator extends AbstractValidator
{
    public bool $basic = false;
    protected array $rules = [];

    public function basic(): static
    {
        $this->rules['identification'] = 'required';
        $this->rules['password'] = 'required';

        $this->basic = true;

        return $this;
    }
}
