<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\users;

use Flarum\Extend;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\PasswordToken;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class PasswordResetTokenAccumulationTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extend(
            (new Extend\Csrf)->exemptRoute('forgot')
        );

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    #[Test]
    public function existing_password_tokens_are_deleted_when_new_reset_is_requested(): void
    {
        // First reset request (unauthenticated)
        $response = $this->send(
            $this->request('POST', '/api/forgot', [
                'json' => ['email' => 'normal@machine.local'],
            ])
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals(1, PasswordToken::query()->where('user_id', 2)->count());

        // Second reset request — should replace the first token, not accumulate
        $response = $this->send(
            $this->request('POST', '/api/forgot', [
                'json' => ['email' => 'normal@machine.local'],
            ])
        );

        $this->assertEquals(204, $response->getStatusCode());

        // Currently fails: old token is NOT deleted, so count is 2
        $this->assertEquals(1, PasswordToken::query()->where('user_id', 2)->count());
    }
}
