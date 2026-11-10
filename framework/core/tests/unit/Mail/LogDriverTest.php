<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Mail;

use Flarum\Mail\FlarumLogTransport;
use Flarum\Mail\LogDriver;
use Flarum\Testing\unit\TestCase;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\RawMessage;

class LogDriverTest extends TestCase
{
    private LoggerInterface $logger;
    private LogDriver $driver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = m::mock(LoggerInterface::class);
        $this->driver = new LogDriver($this->logger);
    }

    // -------------------------------------------------------------------------
    // LogDriver
    // -------------------------------------------------------------------------

    #[Test]
    public function has_no_available_settings(): void
    {
        $this->assertEmpty($this->driver->availableSettings());
    }

    #[Test]
    public function cannot_send(): void
    {
        $this->assertFalse($this->driver->canSend());
    }

    #[Test]
    public function validate_returns_empty_message_bag(): void
    {
        $settings = m::mock(\Flarum\Settings\SettingsRepositoryInterface::class);
        $validator = m::mock(\Illuminate\Contracts\Validation\Factory::class);

        $errors = $this->driver->validate($settings, $validator);

        $this->assertEmpty($errors->all());
    }

    #[Test]
    public function build_transport_returns_flarum_log_transport(): void
    {
        $settings = m::mock(\Flarum\Settings\SettingsRepositoryInterface::class);

        $transport = $this->driver->buildTransport($settings);

        $this->assertInstanceOf(FlarumLogTransport::class, $transport);
    }

    // -------------------------------------------------------------------------
    // FlarumLogTransport behaviour
    // -------------------------------------------------------------------------

    private function envelope(): Envelope
    {
        return new Envelope(new Address('from@example.com'), [new Address('to@example.com')]);
    }

    #[Test]
    public function transport_logs_at_info_level(): void
    {
        $transport = new FlarumLogTransport($this->logger);
        $message = new RawMessage('Hello');

        $this->logger->shouldReceive('info')->once()->with('Hello');

        $transport->send($message, $this->envelope());
    }

    #[Test]
    public function transport_does_not_log_at_debug_level(): void
    {
        $transport = new FlarumLogTransport($this->logger);
        $message = new RawMessage('Hello');

        $this->logger->shouldReceive('info')->once();
        $this->logger->shouldNotReceive('debug');

        $transport->send($message, $this->envelope());
    }

    #[Test]
    public function transport_decodes_quoted_printable_content(): void
    {
        $transport = new FlarumLogTransport($this->logger);

        // =C3=A9 is 'é' in UTF-8 quoted-printable encoding
        $raw = "Content-Transfer-Encoding: quoted-printable\r\n\r\n=C3=A9l=C3=A8ve";
        $message = new RawMessage($raw);

        $this->logger->shouldReceive('info')
            ->once()
            ->with(m::on(function (string $logged) {
                return str_contains($logged, 'élève');
            }));

        $transport->send($message, $this->envelope());
    }

    #[Test]
    public function transport_passes_plain_content_unchanged(): void
    {
        $transport = new FlarumLogTransport($this->logger);
        $message = new RawMessage('Subject: Hello');

        $this->logger->shouldReceive('info')->once()->with('Subject: Hello');

        $transport->send($message, $this->envelope());
    }

    #[Test]
    public function transport_returns_sent_message(): void
    {
        $transport = new FlarumLogTransport($this->logger);
        $message = new RawMessage('Hello');

        $this->logger->shouldReceive('info')->once();

        $result = $transport->send($message, $this->envelope());

        $this->assertInstanceOf(SentMessage::class, $result);
    }
}
