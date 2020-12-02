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
        $nicknameRules = [
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
            $nicknameRules[] = 'unique:users,username';
            $nicknameRules[] = 'unique:users,nickname';
        }

        $validator->setRules([
                'nickname' => $nicknameRules,
            ] + $validator->getRules());
    }
}
