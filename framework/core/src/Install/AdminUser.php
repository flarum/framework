<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install;

use Carbon\Carbon;
use Illuminate\Hashing\BcryptHasher;

class AdminUser
{
    public function __construct(
        private readonly string $username,
        private readonly string $password,
        private readonly string $email
    ) {
        $this->validate();
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getAttributes(): array
    {
        return [
            'username' => $this->username,
            'email' => $this->email,
            'password' => (new BcryptHasher)->make($this->password),
            'joined_at' => Carbon::now(),
            'is_email_confirmed' => 1,
        ];
    }

    private function validate(): void
    {
        if (! filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationFailed('You must enter a valid email.');
        }

        if (! $this->username || preg_match('/[^a-z0-9_-]/i', $this->username)) {
            throw new ValidationFailed('Username can only contain letters, numbers, underscores, and dashes.');
        }
    }
}
