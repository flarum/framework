<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Forum\Auth;

use Flarum\Forum\Auth\Registration;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RegistrationTest extends TestCase
{
    private Registration $registration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registration = new Registration;
    }

    #[Test]
    public function provided_data_is_stored_and_retrieved(): void
    {
        $this->registration->provide('email', 'user@example.com');
        $this->registration->provide('username', 'testuser');

        $this->assertEquals(['email' => 'user@example.com', 'username' => 'testuser'], $this->registration->getProvided());
    }

    #[Test]
    public function suggested_data_is_stored_and_retrieved(): void
    {
        $this->registration->suggest('username', 'suggested_user');

        $this->assertEquals(['username' => 'suggested_user'], $this->registration->getSuggested());
    }

    #[Test]
    public function trusted_email_goes_into_provided(): void
    {
        $this->registration->provideTrustedEmail('trusted@example.com');

        $provided = $this->registration->getProvided();
        $this->assertEquals('trusted@example.com', $provided['email']);
    }

    #[Test]
    public function suggest_username_sanitizes_to_alphanumeric(): void
    {
        $this->registration->suggestUsername('Hello World! @#$');

        $suggested = $this->registration->getSuggested();
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_-]+$/', $suggested['username']);
    }

    #[Test]
    public function suggest_username_handles_unicode(): void
    {
        $this->registration->suggestUsername('José García');

        $suggested = $this->registration->getSuggested();
        $this->assertArrayHasKey('username', $suggested);
        $this->assertNotEmpty($suggested['username']);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_-]+$/', $suggested['username']);
    }

    #[Test]
    public function provide_avatar_stores_url(): void
    {
        $this->registration->provideAvatar('https://example.com/avatar.jpg');

        $provided = $this->registration->getProvided();
        $this->assertEquals('https://example.com/avatar.jpg', $provided['avatar_url']);
    }

    #[Test]
    public function payload_is_stored_and_retrieved(): void
    {
        $payload = ['raw_data' => 'from_provider', 'extra' => 42];
        $this->registration->setPayload($payload);

        $this->assertEquals($payload, $this->registration->getPayload());
    }

    #[Test]
    public function provided_and_suggested_are_independent(): void
    {
        $this->registration->provide('email', 'provided@example.com');
        $this->registration->suggest('email', 'suggested@example.com');

        $this->assertEquals(['email' => 'provided@example.com'], $this->registration->getProvided());
        $this->assertEquals(['email' => 'suggested@example.com'], $this->registration->getSuggested());
    }

    #[Test]
    public function empty_registration_returns_empty_arrays(): void
    {
        $this->assertEquals([], $this->registration->getProvided());
        $this->assertEquals([], $this->registration->getSuggested());
        $this->assertNull($this->registration->getPayload());
    }
}
