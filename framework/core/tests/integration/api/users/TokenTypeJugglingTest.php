<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\users;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\EmailToken;
use Flarum\User\Exception\InvalidConfirmationTokenException;
use Flarum\User\PasswordToken;
use Flarum\User\RegistrationToken;

class TokenTypeJugglingTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ],
        ]);
    }

    /**
     * A letter-leading token that MySQL/MariaDB coerces to `0` when compared
     * against an integer. ~84% of Str::random(40) tokens start with a letter,
     * so this stands in for a real, unseen token.
     */
    private const LETTER_LEADING_TOKEN = 'Zpwned000000000000000000000000000000000';

    /** @test */
    public function password_token_is_not_matched_by_integer_zero()
    {
        $this->app();

        $token = PasswordToken::generate(1);
        $token->token = self::LETTER_LEADING_TOKEN;
        $token->save();

        // A bare JSON integer 0 must not resolve the letter-leading token.
        $this->assertNull(PasswordToken::query()->find(0));

        $this->expectException(InvalidConfirmationTokenException::class);
        PasswordToken::validOrFail(0);
    }

    /** @test */
    public function email_token_is_not_matched_by_integer_zero()
    {
        $this->app();

        $token = EmailToken::generate('juggled@machine.local', 1);
        $token->token = self::LETTER_LEADING_TOKEN;
        $token->save();

        $this->assertNull(EmailToken::query()->find(0));

        $this->expectException(InvalidConfirmationTokenException::class);
        EmailToken::validOrFail(0);
    }

    /** @test */
    public function registration_token_is_not_matched_by_integer_zero()
    {
        $this->app();

        $token = RegistrationToken::generate('test', 'juggled', [], []);
        $token->token = self::LETTER_LEADING_TOKEN;
        $token->save();

        $this->assertNull(RegistrationToken::query()->find(0));

        $this->expectException(InvalidConfirmationTokenException::class);
        RegistrationToken::validOrFail(0);
    }
}
