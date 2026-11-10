<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\access_tokens;

use Flarum\Http\RememberAccessToken;
use Flarum\Http\SessionAccessToken;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * The admin panel writes these as ordinary settings, so what it saves has to be
 * what the token classes read back.
 */
class SessionSettingsTest extends TestCase
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

    #[Test]
    public function an_admin_can_save_a_token_lifetime()
    {
        $response = $this->send(
            $this->request('POST', '/api/settings', [
                'authenticatedAs' => 1,
                'json' => [
                    'session.tokens.session' => 1800,
                ],
            ])
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals(1800, SessionAccessToken::lifetime());
    }

    #[Test]
    public function a_saved_lifetime_applies_to_the_type_it_names_only()
    {
        $this->send(
            $this->request('POST', '/api/settings', [
                'authenticatedAs' => 1,
                'json' => [
                    'session.tokens.session' => 1800,
                ],
            ])
        );

        $this->assertEquals(5 * 365 * 24 * 60 * 60, RememberAccessToken::lifetime());
    }

    #[Test]
    public function a_normal_user_cannot_save_session_settings()
    {
        // User 2 is the non-admin seeded in setUp().
        $response = $this->send(
            $this->request('POST', '/api/settings', [
                'authenticatedAs' => 2,
                'json' => [
                    'session.tokens.session' => 60,
                ],
            ])
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals(60 * 60, SessionAccessToken::lifetime());
    }

    #[Test]
    public function config_still_wins_over_a_saved_setting()
    {
        $this->config('session.tokens.session', 900);

        $this->send(
            $this->request('POST', '/api/settings', [
                'authenticatedAs' => 1,
                'json' => [
                    'session.tokens.session' => 1800,
                ],
            ])
        );

        // The setting is stored, but the pinned value is what applies.
        $this->assertEquals(900, SessionAccessToken::lifetime());
    }
}
