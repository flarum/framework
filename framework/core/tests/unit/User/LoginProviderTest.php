<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\User;

use Flarum\Testing\unit\TestCase;
use Flarum\User\LoginProvider;
use PHPUnit\Framework\Attributes\Test;

class LoginProviderTest extends TestCase
{
    #[Test]
    public function updated_at_column_is_last_login_at(): void
    {
        $this->assertEquals('last_login_at', LoginProvider::UPDATED_AT);
    }

    #[Test]
    public function fillable_includes_provider_and_identifier(): void
    {
        $provider = new LoginProvider;

        // Verify the fillable fields via mass assignment
        $provider->fill(['provider' => 'github', 'identifier' => '12345', 'user_id' => 99]);

        $this->assertEquals('github', $provider->provider);
        $this->assertEquals('12345', $provider->identifier);
        // user_id is not fillable — should not be set
        $this->assertNull($provider->user_id);
    }

    #[Test]
    public function timestamps_are_cast_to_datetime(): void
    {
        $provider = new LoginProvider;
        $casts = $provider->getCasts();

        $this->assertArrayHasKey('created_at', $casts);
        $this->assertArrayHasKey('last_login_at', $casts);
    }
}
