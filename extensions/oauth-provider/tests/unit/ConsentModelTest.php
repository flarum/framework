<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Tests\unit;

use Flarum\OAuthProvider\Models\Consent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ConsentModelTest extends TestCase
{
    #[Test]
    public function covers_returns_true_when_all_requested_scopes_are_granted(): void
    {
        $consent = new Consent();
        $consent->scopes = ['openid', 'profile', 'email'];

        $this->assertTrue($consent->covers(['openid', 'profile']));
        $this->assertTrue($consent->covers(['openid', 'profile', 'email']));
        $this->assertTrue($consent->covers(['email']));
    }

    #[Test]
    public function covers_returns_false_when_any_requested_scope_is_missing(): void
    {
        $consent = new Consent();
        $consent->scopes = ['openid', 'profile'];

        $this->assertFalse($consent->covers(['openid', 'profile', 'email']));
        $this->assertFalse($consent->covers(['invoices:read']));
    }

    #[Test]
    public function covers_returns_true_for_empty_request(): void
    {
        $consent = new Consent();
        $consent->scopes = ['openid'];

        $this->assertTrue($consent->covers([]));
    }

    #[Test]
    public function covers_returns_false_when_consent_has_no_scopes(): void
    {
        $consent = new Consent();
        $consent->scopes = null;

        $this->assertFalse($consent->covers(['openid']));
    }

    #[Test]
    public function covers_returns_true_when_both_consent_and_request_are_empty(): void
    {
        $consent = new Consent();
        $consent->scopes = null;

        $this->assertTrue($consent->covers([]));
    }
}
