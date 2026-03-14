<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Mail;

use Flarum\Mail\MailgunDriver;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\unit\TestCase;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as ValidatorFactory;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mailer\Bridge\Mailgun\Transport\MailgunApiTransport;

class MailgunDriverTest extends TestCase
{
    private MailgunDriver $driver;
    private ValidatorFactory $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->driver = new MailgunDriver();
        $this->validator = new ValidatorFactory(new Translator(new ArrayLoader(), 'en'));
    }

    private function settings(array $values): SettingsRepositoryInterface
    {
        $settings = m::mock(SettingsRepositoryInterface::class);
        $settings->shouldReceive('all')->andReturn($values);
        $settings->shouldReceive('get')->andReturnUsing(fn ($key) => $values[$key] ?? null);

        return $settings;
    }

    private function validSettings(): array
    {
        return [
            'mail_mailgun_secret' => 'key-abc123',
            'mail_mailgun_domain' => 'mg.example.com',
            'mail_mailgun_region' => 'api.mailgun.net',
        ];
    }

    // -------------------------------------------------------------------------
    // Driver metadata
    // -------------------------------------------------------------------------

    #[Test]
    public function can_send(): void
    {
        $this->assertTrue($this->driver->canSend());
    }

    #[Test]
    public function available_settings_contains_expected_keys(): void
    {
        $settings = $this->driver->availableSettings();

        $this->assertArrayHasKey('mail_mailgun_secret', $settings);
        $this->assertArrayHasKey('mail_mailgun_domain', $settings);
        $this->assertArrayHasKey('mail_mailgun_region', $settings);
    }

    #[Test]
    public function region_setting_is_a_dropdown_with_us_and_eu(): void
    {
        $settings = $this->driver->availableSettings();

        $this->assertIsArray($settings['mail_mailgun_region']);
        $this->assertArrayHasKey('api.mailgun.net', $settings['mail_mailgun_region']);
        $this->assertArrayHasKey('api.eu.mailgun.net', $settings['mail_mailgun_region']);
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    #[Test]
    public function validation_passes_for_valid_us_configuration(): void
    {
        $errors = $this->driver->validate($this->settings($this->validSettings()), $this->validator);

        $this->assertEmpty($errors->all());
    }

    #[Test]
    public function validation_passes_for_eu_region(): void
    {
        $settings = array_merge($this->validSettings(), ['mail_mailgun_region' => 'api.eu.mailgun.net']);

        $errors = $this->driver->validate($this->settings($settings), $this->validator);

        $this->assertEmpty($errors->all());
    }

    #[Test]
    public function validation_fails_when_secret_is_missing(): void
    {
        $settings = $this->validSettings();
        unset($settings['mail_mailgun_secret']);

        $errors = $this->driver->validate($this->settings($settings), $this->validator);

        $this->assertArrayHasKey('mail_mailgun_secret', $errors->toArray());
    }

    #[Test]
    public function validation_fails_when_domain_is_missing(): void
    {
        $settings = $this->validSettings();
        unset($settings['mail_mailgun_domain']);

        $errors = $this->driver->validate($this->settings($settings), $this->validator);

        $this->assertArrayHasKey('mail_mailgun_domain', $errors->toArray());
    }

    #[Test]
    public function validation_fails_when_domain_is_invalid(): void
    {
        $settings = array_merge($this->validSettings(), ['mail_mailgun_domain' => 'not a domain!']);

        $errors = $this->driver->validate($this->settings($settings), $this->validator);

        $this->assertArrayHasKey('mail_mailgun_domain', $errors->toArray());
    }

    #[Test]
    public function validation_fails_when_region_is_not_recognised(): void
    {
        $settings = array_merge($this->validSettings(), ['mail_mailgun_region' => 'api.invalid.net']);

        $errors = $this->driver->validate($this->settings($settings), $this->validator);

        $this->assertArrayHasKey('mail_mailgun_region', $errors->toArray());
    }

    // -------------------------------------------------------------------------
    // Transport construction
    // -------------------------------------------------------------------------

    #[Test]
    public function build_transport_returns_mailgun_api_transport(): void
    {
        $transport = $this->driver->buildTransport($this->settings($this->validSettings()));

        $this->assertInstanceOf(MailgunApiTransport::class, $transport);
    }
}
