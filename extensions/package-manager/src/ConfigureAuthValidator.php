<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager;

use Flarum\Foundation\AbstractValidator;

class ConfigureAuthValidator extends AbstractValidator
{
    use AllValidatorRules;

    protected $rules = [
        'github-oauth' => ['sometimes', 'array'],
        'github-oauth.*' => ['sometimes', 'string'],
        'gitlab-oauth' => ['sometimes', 'array'],
        'gitlab-oauth.*' => ['sometimes', 'string'],
        'gitlab-token' => ['sometimes', 'array'],
        'gitlab-token.*' => ['sometimes', 'string'],
        'bearer' => ['sometimes', 'array'],
        'bearer.*' => ['sometimes', 'string'],
    ];

    /**
     * {@inheritdoc}
     */
    protected function attributes()
    {
        return [
            'github-oauth' => $this->translator->trans('flarum-extension-manager.validation.attributes.github_oauth'),
            'github-oauth.*' => $this->translator->trans('flarum-extension-manager.validation.attributes.github_oauth_*'),
            'gitlab-oauth' => $this->translator->trans('flarum-extension-manager.validation.attributes.gitlab_oauth'),
            'gitlab-oauth.*' => $this->translator->trans('flarum-extension-manager.validation.attributes.gitlab_oauth_*'),
            'gitlab-token' => $this->translator->trans('flarum-extension-manager.validation.attributes.gitlab_token'),
            'gitlab-token.*' => $this->translator->trans('flarum-extension-manager.validation.attributes.gitlab_token_*'),
            'bearer' => $this->translator->trans('flarum-extension-manager.validation.attributes.bearer'),
            'bearer.*' => $this->translator->trans('flarum-extension-manager.validation.attributes.bearer_*'),
        ];
    }
}
