<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tests\integration;

trait RetrievesAuthorizedUsers
{
    protected function adminGroup(): array
    {
        return [
            'id' => 1,
            'name_singular' => 'Admin',
            'name_plural' => 'Admins',
            'color' => '#B72A2A',
            'icon' => 'fas fa-wrench',
        ];
    }

    protected function guestGroup(): array
    {
        return [
            'id' => 2,
            'name_singular' => 'Guest',
            'name_plural' => 'Guests',
            'color' => null,
            'icon' => null,
        ];
    }

    protected function memberGroup(): array
    {
        return [
            'id' => 3,
            'name_singular' => 'Member',
            'name_plural' => 'Members',
            'color' => null,
            'icon' => null,
        ];
    }

    protected function adminUser(): array
    {
        return [
            'id' => 1,
            'username' => 'admin',
            'password' => '$2y$10$HMOAe.XaQjOimA778VmFue1OCt7tj5j0wk5vfoL/CMSJq2BQlfBV2', // BCrypt hash for "password"
            'email' => 'admin@machine.local',
            'is_email_confirmed' => 1,
        ];
    }

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
