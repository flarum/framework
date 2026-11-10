<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\access_tokens;

use Carbon\Carbon;
use Flarum\Http\AccessToken;
use Flarum\Http\DeveloperAccessToken;
use Flarum\Http\RememberAccessToken;
use Flarum\Http\SessionAccessToken;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * How long a token stays valid is resolved rather than fixed: `config.php`
 * first, then the settings table, then the lifetime the class itself declares.
 *
 * The order matters more than it looks. Sites that never configure anything
 * must keep exactly the lifetimes they have today, and an admin must not be
 * able to contradict a value the server owner has pinned in `config.php`.
 */
class AccessTokenLifetimeConfigTest extends TestCase
{
    #[Test]
    public function session_tokens_last_an_hour_by_default()
    {
        $this->app();

        $this->assertEquals(60 * 60, SessionAccessToken::lifetime());
    }

    #[Test]
    public function remember_tokens_last_five_years_by_default()
    {
        $this->app();

        $this->assertEquals(5 * 365 * 24 * 60 * 60, RememberAccessToken::lifetime());
    }

    #[Test]
    public function developer_tokens_never_expire_by_default()
    {
        $this->app();

        // Zero is not "expires immediately" but "no expiry at all", which is
        // what the query scopes read it as.
        $this->assertEquals(0, DeveloperAccessToken::lifetime());
    }

    #[Test]
    public function a_setting_overrides_the_class_default()
    {
        $this->setting('session.tokens.session', 1800);

        $this->app();

        $this->assertEquals(1800, SessionAccessToken::lifetime());
    }

    #[Test]
    public function config_overrides_a_setting()
    {
        $this->setting('session.tokens.session', 1800);
        $this->config('session.tokens.session', 900);

        $this->app();

        $this->assertEquals(900, SessionAccessToken::lifetime());
    }

    #[Test]
    public function each_type_is_configured_independently()
    {
        $this->config('session.tokens.session', 900);

        $this->app();

        $this->assertEquals(900, SessionAccessToken::lifetime());
        $this->assertEquals(5 * 365 * 24 * 60 * 60, RememberAccessToken::lifetime());
    }

    #[Test]
    public function a_configured_lifetime_decides_which_tokens_are_expired()
    {
        // Half an hour, so a token last used at 02:00 is expired by 02:45 —
        // which the default hour would not be.
        $this->config('session.tokens.session', 1800);

        $this->prepareDatabase([
            AccessToken::class => [
                ['token' => 'a', 'user_id' => 1, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'session'],
            ],
        ]);

        $this->populateDatabase();

        $this->assertEquals([], AccessToken::whereExpired(Carbon::parse('2021-01-01 02:15:00'))->pluck('token')->all());
        $this->assertEquals(['a'], AccessToken::whereExpired(Carbon::parse('2021-01-01 02:45:00'))->pluck('token')->all());
    }

    #[Test]
    public function a_configured_lifetime_decides_which_tokens_are_valid()
    {
        $this->config('session.tokens.session', 1800);

        $this->prepareDatabase([
            AccessToken::class => [
                ['token' => 'a', 'user_id' => 1, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'session'],
            ],
        ]);

        $this->populateDatabase();

        $this->assertEquals(['a'], AccessToken::whereValid(Carbon::parse('2021-01-01 02:15:00'))->pluck('token')->all());
        $this->assertEquals([], AccessToken::whereValid(Carbon::parse('2021-01-01 02:45:00'))->pluck('token')->all());
    }

    #[Test]
    public function a_lifetime_of_zero_means_no_expiry_rather_than_instant_expiry()
    {
        $this->config('session.tokens.session', 0);

        $this->prepareDatabase([
            AccessToken::class => [
                ['token' => 'a', 'user_id' => 1, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'session'],
            ],
        ]);

        $this->populateDatabase();

        $this->assertEquals([], AccessToken::whereExpired(Carbon::parse('2030-01-01 00:00:00'))->pluck('token')->all());
        $this->assertEquals(['a'], AccessToken::whereValid(Carbon::parse('2030-01-01 00:00:00'))->pluck('token')->all());
    }

    #[Test]
    public function a_negative_lifetime_is_ignored_rather_than_expiring_everything()
    {
        // Nothing sensible can be done with a negative lifetime, and treating
        // it as "already expired" would log everyone out over a typo.
        $this->config('session.tokens.session', -60);

        $this->app();

        $this->assertEquals(60 * 60, SessionAccessToken::lifetime());
    }

    #[Test]
    public function a_non_numeric_lifetime_is_ignored()
    {
        $this->config('session.tokens.session', 'not a number');

        $this->app();

        $this->assertEquals(60 * 60, SessionAccessToken::lifetime());
    }
}
