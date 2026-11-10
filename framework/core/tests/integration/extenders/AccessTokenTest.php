<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Carbon\Carbon;
use Flarum\Extend;
use Flarum\Http\AccessToken;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AccessTokenTest extends TestCase
{
    #[Test]
    public function token_types_are_registered_without_the_extender()
    {
        $this->app();

        $this->assertArrayHasKey('session', AccessToken::getModels());
        $this->assertArrayHasKey('session_remember', AccessToken::getModels());
        $this->assertArrayHasKey('developer', AccessToken::getModels());
    }

    #[Test]
    public function extender_registers_a_new_type()
    {
        $this->extend(
            (new Extend\AccessToken())
                ->type(SecondFactorToken::class)
        );

        $this->app();

        $this->assertArrayHasKey('second_factor', AccessToken::getModels());
        $this->assertEquals(SecondFactorToken::class, AccessToken::getModels()['second_factor']);
    }

    #[Test]
    public function a_registered_type_is_hydrated_as_its_own_class()
    {
        $this->extend(
            (new Extend\AccessToken())
                ->type(SecondFactorToken::class)
        );

        $this->prepareDatabase([
            AccessToken::class => [
                ['token' => 'a', 'user_id' => 1, 'last_activity_at' => Carbon::now(), 'type' => 'second_factor'],
            ],
        ]);

        $this->populateDatabase();

        $this->assertInstanceOf(SecondFactorToken::class, AccessToken::query()->where('token', 'a')->first());
    }

    #[Test]
    public function a_registered_type_takes_its_lifetime_from_config()
    {
        $this->extend(
            (new Extend\AccessToken())
                ->type(SecondFactorToken::class)
        );

        $this->config('session.tokens.second_factor', 60);

        $this->app();

        $this->assertEquals(60, SecondFactorToken::lifetime());
    }

    #[Test]
    public function a_registered_type_takes_its_lifetime_from_settings()
    {
        $this->extend(
            (new Extend\AccessToken())
                ->type(SecondFactorToken::class)
        );

        $this->setting('session.tokens.second_factor', 120);

        $this->app();

        $this->assertEquals(120, SecondFactorToken::lifetime());
    }

    #[Test]
    public function a_registered_type_keeps_its_own_default_when_nothing_is_configured()
    {
        $this->extend(
            (new Extend\AccessToken())
                ->type(SecondFactorToken::class)
        );

        $this->app();

        $this->assertEquals(300, SecondFactorToken::lifetime());
    }

    #[Test]
    public function a_registered_lifetime_decides_expiry()
    {
        $this->extend(
            (new Extend\AccessToken())
                ->type(SecondFactorToken::class)
        );

        $this->config('session.tokens.second_factor', 60);

        $this->prepareDatabase([
            AccessToken::class => [
                ['token' => 'a', 'user_id' => 1, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'second_factor'],
            ],
        ]);

        $this->populateDatabase();

        $this->assertEquals([], AccessToken::whereExpired(Carbon::parse('2021-01-01 02:00:30'))->pluck('token')->all());
        $this->assertEquals(['a'], AccessToken::whereExpired(Carbon::parse('2021-01-01 02:02:00'))->pluck('token')->all());
    }

    #[Test]
    public function a_type_can_opt_out_of_being_configured()
    {
        $this->extend(
            (new Extend\AccessToken())
                ->type(FixedLifetimeToken::class)
        );

        $this->config('session.tokens.fixed', 60);

        $this->app();

        $this->assertFalse(FixedLifetimeToken::hasConfigurableLifetime());
        $this->assertEquals(900, FixedLifetimeToken::lifetime());
    }

    #[Test]
    public function core_types_that_opt_out_cannot_be_configured()
    {
        // Developer tokens are meant to outlive sessions; an expiry set in the
        // admin panel would break integrations rather than tighten security.
        $this->config('session.tokens.developer', 60);

        $this->app();

        $this->assertEquals(0, \Flarum\Http\DeveloperAccessToken::lifetime());
    }
}

class SecondFactorToken extends AccessToken
{
    public static string $type = 'second_factor';

    protected static int $lifetime = 300;
}

class FixedLifetimeToken extends AccessToken
{
    public static string $type = 'fixed';

    protected static int $lifetime = 900;

    protected static bool $configurableLifetime = false;
}
