<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Testing\integration;

trait RetrievesAuthorizedUsers
{
    protected function normalUser(): array
    {
        return [
            'id' => 2,
            'username' => 'normal',
            'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', // BCrypt hash for "too-obscure"
            'email' => 'normal@machine.local',
            'is_email_confirmed' => 1,
        ];
    }
}
