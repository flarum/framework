<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager;

use Flarum\Foundation\AbstractValidator;

class ConfigureComposerValidator extends AbstractValidator
{
    protected $rules = [
        'minimum-stability' => ['sometimes', 'in:stable,RC,beta,alpha,dev'],
    ];
}
