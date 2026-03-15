<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Mail;

use Flarum\Mail\PostmarkDriver;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\unit\TestCase;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as ValidatorFactory;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mailer\Bridge\Postmark\Transport\PostmarkApiTransport;

class PostmarkDriverTest extends TestCase
{
    private PostmarkDriver $driver;
    private ValidatorFactory $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->driver = new PostmarkDriver();
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
            'mail_postmark_token' => 'abc123def456',
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

        $this->assertArrayHasKey('mail_postmark_token', $settings);
        $this->assertArrayHasKey('mail_postmark_message_stream', $settings);
    }

    #[Test]
    public function settings_are_plain_string_inputs_not_dropdowns(): void
    {
        $settings = $this->driver->availableSettings();

        $this->assertSame('', $settings['mail_postmark_token']);
        $this->assertSame('', $settings['mail_postmark_message_stream']);
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    #[Test]
    public function validation_passes_for_valid_configuration(): void
    {
        $errors = $this->driver->validate($this->settings($this->validSettings()), $this->validator);

        $this->assertEmpty($errors->all());
    }

    #[Test]
    public function validation_passes_when_message_stream_is_provided(): void
    {
        $settings = array_merge($this->validSettings(), ['mail_postmark_message_stream' => 'outbound']);

        $errors = $this->driver->validate($this->settings($settings), $this->validator);

        $this->assertEmpty($errors->all());
    }

    #[Test]
    public function validation_fails_when_token_is_missing(): void
    {
        $errors = $this->driver->validate($this->settings([]), $this->validator);

        $this->assertArrayHasKey('mail_postmark_token', $errors->toArray());
    }

    #[Test]
    public function validation_fails_when_token_has_leading_whitespace(): void
    {
        $settings = ['mail_postmark_token' => ' abc123'];

        $errors = $this->driver->validate($this->settings($settings), $this->validator);

        $this->assertNotEmpty($errors->all());
    }

    #[Test]
    public function validation_fails_when_token_has_trailing_whitespace(): void
    {
        $settings = ['mail_postmark_token' => 'abc123 '];

        $errors = $this->driver->validate($this->settings($settings), $this->validator);

        $this->assertNotEmpty($errors->all());
    }

    // -------------------------------------------------------------------------
    // Transport construction
    // -------------------------------------------------------------------------

    #[Test]
    public function build_transport_returns_postmark_api_transport(): void
    {
        $transport = $this->driver->buildTransport($this->settings($this->validSettings()));

        $this->assertInstanceOf(PostmarkApiTransport::class, $transport);
    }

    #[Test]
    public function build_transport_without_message_stream_succeeds(): void
    {
        $transport = $this->driver->buildTransport($this->settings($this->validSettings()));

        $this->assertInstanceOf(PostmarkApiTransport::class, $transport);
        $this->assertStringNotContainsString('outbound', (string) $transport);
    }

    #[Test]
    public function build_transport_with_message_stream_includes_it(): void
    {
        $settings = array_merge($this->validSettings(), ['mail_postmark_message_stream' => 'outbound']);

        $transport = $this->driver->buildTransport($this->settings($settings));

        $this->assertInstanceOf(PostmarkApiTransport::class, $transport);
        $this->assertStringContainsString('outbound', (string) $transport);
    }
}
