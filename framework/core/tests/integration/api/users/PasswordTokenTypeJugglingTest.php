<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\users;

use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\PasswordToken;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Regression guard for GHSA-55f2-h36g-96c3.
 *
 * The password-reset token is a random 40-character string used as the primary
 * key of PasswordToken. If the model does not declare its key as a string,
 * Eloquent treats the key as an integer, and on MySQL/MariaDB a lookup for the
 * integer 0 coerces every token whose value begins with a letter — about 84% of
 * them — to 0. An unauthenticated request sending `passwordToken: 0` (a bare
 * JSON integer, not a quoted string) then matches a real, unseen token and
 * takes the account over.
 *
 * 2.x avoids this, and these tests pin *why* so a refactor cannot quietly bring
 * it back.
 */
class PasswordTokenTypeJugglingTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            'password_tokens' => [
                // A token that begins with a letter, as ~84% of generated ones
                // do. This is the value the integer 0 must not be allowed to
                // match.
                ['token' => 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2', 'user_id' => 2, 'created_at' => Carbon::now()],
            ],
        ]);
    }

    #[Test]
    public function all_token_models_declare_a_string_key()
    {
        // The structural guarantee, for every token looked up by its string
        // value. Without this, Eloquent binds the lookup as an integer and the
        // attack becomes possible again — so all three the advisory names are
        // pinned, not just the password one it was reported against.
        $this->app();

        $this->assertSame('string', (new PasswordToken)->getKeyType());
        $this->assertSame('string', (new \Flarum\User\EmailToken)->getKeyType());
        $this->assertSame('string', (new \Flarum\User\RegistrationToken)->getKeyType());
    }

    #[Test]
    public function a_bare_integer_token_does_not_match_a_letter_leading_token()
    {
        // The attack itself, end to end: reset the victim's password with a
        // bare integer token and confirm it is refused.
        $this->app();

        $original = User::find(2)->password;

        $response = $this->send(
            $this->request('POST', '/reset', [
                'json' => [
                    'passwordToken' => 0,
                    'password' => 'attacker-chosen-password',
                    'password_confirmation' => 'attacker-chosen-password',
                ],
            ])
        );

        // The reset must not have succeeded.
        $this->assertNotEquals(200, $response->getStatusCode(), 'A bare integer token was accepted.');

        // And the victim's password must be untouched.
        $this->assertSame($original, User::find(2)->refresh()->password, 'The victim password was changed.');
    }

    #[Test]
    public function the_lookup_binds_the_token_as_a_string_not_an_integer()
    {
        // The mechanism, checked at the query layer so it holds on every
        // database driver, not only the ones where the coercion happens to be
        // visible. A string key type makes Eloquent bind `0` as `'0'`, which
        // cannot equal a letter-leading token.
        $this->app();

        $db = $this->database();
        $db->flushQueryLog();
        $db->enableQueryLog();

        PasswordToken::find(0);

        $log = $db->getQueryLog();
        $db->disableQueryLog();

        $last = end($log);
        $binding = $last['bindings'][0] ?? null;

        $this->assertSame('0', $binding, 'The token lookup bound an integer, not a string.');
    }
}
