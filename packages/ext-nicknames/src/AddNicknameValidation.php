<?php


namespace Flarum\Nicknames;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Validation\Validator;

class AddNicknameValidation
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;


    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    public function __invoke($flarumValidator, Validator $validator)
    {
        $idSuffix = $flarumValidator->getUser() ? ','.$flarumValidator->getUser()->id : '';
        $rules = $validator->getRules();

        $rules['nickname'] = [
            function ($attribute, $value, $fail) {
                $regex = $this->settings->get('flarum-nicknames.regex');
                if ($regex && !preg_match_all("/$regex/", $value)) {
                    $fail(app('translator')->trans('flarum-nicknames.api.invalid_nickname_message'));
                }
            },
            'min:' . $this->settings->get('flarum-nicknames.min'),
            'max:' . $this->settings->get('flarum-nicknames.max'),
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
