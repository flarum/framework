<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Nicknames\Api;

use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\Locale\TranslatorInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;

class UserResourceFields
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected TranslatorInterface $translator
    ) {
    }

    public function __invoke(): array
    {
        $regex = $this->settings->get('flarum-nicknames.regex');

        if (! empty($regex)) {
            $regex = "/$regex/";
        }

        return [
            Schema\Str::make('nickname')
                ->visible(false)
                ->writable(function (User $user, Context $context) {
                    return $context->getActor()->can('editNickname', $user);
                })
                ->nullable()
                ->regex($regex ?? '', ! empty($regex))
                ->minLength($this->settings->get('flarum-nicknames.min'))
                ->maxLength($this->settings->get('flarum-nicknames.max'))
                ->unique('users', 'nickname', true, (bool) $this->settings->get('flarum-nicknames.unique'))
                ->unique('users', 'username', true, (bool) $this->settings->get('flarum-nicknames.unique'))
                ->validationMessages([
                    'nickname.regex' => $this->translator->trans('flarum-nicknames.api.invalid_nickname_message'),
                ])
                ->set(function (User $user, ?string $nickname) {
                    $user->nickname = $user->username === $nickname ? null : $nickname;
                }),
            Schema\Boolean::make('canEditNickname')
                ->get(fn (User $user, Context $context) => $context->getActor()->can('editNickname', $user)),
        ];
    }

    public static function username(Schema\Str $field): Schema\Str
    {
        return $field->unique('users', 'nickname', true, (bool) resolve(SettingsRepositoryInterface::class)->get('flarum-nicknames.unique'));
    }
}
