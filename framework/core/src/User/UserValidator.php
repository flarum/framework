<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\Foundation\AbstractValidator;

class UserValidator extends AbstractValidator
{
    protected ?User $user = null;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    protected function getRules(): array
    {
        $idSuffix = $this->user ? ','.$this->user->id : '';

        return [
            'username' => [
                'required',
                'regex:/^[a-z0-9_-]+$/i',
                'unique:users,username'.$idSuffix,
                'min:3',
                'max:30'
            ],
            'email' => [
                'required',
                'email:filter',
                'unique:users,email'.$idSuffix
            ],
            'password' => [
                'required',
                'min:8'
            ]
        ];
    }

    protected function getMessages(): array
    {
        return [
            'username.regex' => $this->translator->trans('core.api.invalid_username_message')
        ];
    }
}
