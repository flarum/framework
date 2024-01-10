<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager;

use Flarum\Foundation\AbstractValidator;

class ConfigureComposerValidator extends AbstractValidator
{
    protected $rules = [
        'composer' => [
            'minimum-stability' => ['sometimes', 'in:stable,RC,beta,alpha,dev'],
            'repositories' => ['sometimes', 'array'],
            'repositories.*.type' => ['sometimes', 'in:composer,vcs,path'],
            'repositories.*.url' => ['sometimes', 'string'],
        ],
        'auth' => [
            'github-oauth' => ['sometimes', 'array'],
            'github-oauth.*' => ['sometimes', 'string'],
            'gitlab-oauth' => ['sometimes', 'array'],
            'gitlab-oauth.*' => ['sometimes', 'string'],
            'gitlab-token' => ['sometimes', 'array'],
            'gitlab-token.*' => ['sometimes', 'string'],
            'bearer' => ['sometimes', 'array'],
            'bearer.*' => ['sometimes', 'string'],
        ],
    ];
}
