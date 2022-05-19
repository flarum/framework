<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Nicknames;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Validation\Validator;
use Symfony\Contracts\Translation\TranslatorInterface;

class AddNicknameValidation
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(SettingsRepositoryInterface $settings, TranslatorInterface $translator)
    {
        $this->settings = $settings;
        $this->translator = $translator;
    }

    public function __invoke($flarumValidator, Validator $validator)
    {
        $idSuffix = $flarumValidator->getUser() ? ','.$flarumValidator->getUser()->id : '';
        $rules = $validator->getRules();

        $rules['nickname'] = [
            function ($attribute, $value, $fail) {
                $regex = $this->settings->get('flarum-nicknames.regex');
                if ($regex && ! preg_match_all("/$regex/", $value)) {
                    $fail($this->translator->trans('flarum-nicknames.api.invalid_nickname_message'));
                }
            },
            'min:'.$this->settings->get('flarum-nicknames.min'),
            'max:'.$this->settings->get('flarum-nicknames.max'),
            'nullable'
        ];

        if ($this->settings->get('flarum-nicknames.unique')) {
            $rules['nickname'][] = 'unique:users,username'.$idSuffix;
            $rules['nickname'][] = 'unique:users,nickname'.$idSuffix;
            $rules['username'][] = 'unique:users,nickname'.$idSuffix;
        }

        $validator->setRules($rules);
    }
}
