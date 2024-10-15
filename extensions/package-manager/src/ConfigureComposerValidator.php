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
    use AllValidatorRules;

    protected $rules = [
        'minimum-stability' => ['sometimes', 'in:stable,RC,beta,alpha,dev'],
        'repositories' => ['sometimes', 'array'],
        'repositories.*' => ['sometimes', 'array', 'required_array_keys:type,url'],
        'repositories.*.type' => ['in:composer,vcs,path'],
        'repositories.*.url' => ['string', 'filled'],
    ];

    /**
     * {@inheritdoc}
     */
    protected function attributes()
    {
        return [
            'minimum-stability' => $this->translator->trans('validation.attributes.minimum_stability'),
            'repositories' => $this->translator->trans('validation.attributes.repositories'),
            'repositories.*' => $this->translator->trans('validation.attributes.repositories_*'),
            'repositories.*.type' => $this->translator->trans('validation.attributes.repositories_*_type'),
            'repositories.*.url' => $this->translator->trans('validation.attributes.repositories_*_url'),
        ];
    }
}
