<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Mail;

use Flarum\Mail\Mailer;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\unit\TestCase;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Mailer\Transport\TransportInterface;

class MailerTest extends TestCase
{
    private SettingsRepositoryInterface $settings;
    private LoggerInterface $logger;
    private Mailer $mailer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settings = m::mock(SettingsRepositoryInterface::class);
        $this->logger = m::mock(LoggerInterface::class);

        $this->settings->shouldReceive('get')->with('mail_format')->andReturn('multipart');

        $views = m::mock(Factory::class);
        $views->shouldReceive('make')->andReturn(m::mock(\Illuminate\Contracts\View\View::class)->shouldIgnoreMissing());
        $views->shouldReceive('exists')->andReturn(true);
        $views->shouldReceive('share')->andReturnSelf();

        $transport = m::mock(TransportInterface::class);

        $dispatcher = m::mock(Dispatcher::class);
        $dispatcher->shouldReceive('until')->andReturn(null);
        $dispatcher->shouldReceive('dispatch')->andReturn(null);

        $this->mailer = new Mailer('flarum', $views, $transport, $dispatcher, $this->settings, $this->logger);
    }

    #[Test]
    public function successful_send_does_not_log_anything(): void
    {
        $this->logger->shouldNotReceive('error');

        // parent::send() will try to actually send — stub at the transport level isn't straightforward,
        // so we verify the logger is never called when no exception is thrown by using a partial mock.
        $mailer = m::mock(Mailer::class, [
            'flarum',
            m::mock(Factory::class)->shouldIgnoreMissing(),
            m::mock(TransportInterface::class),
            m::mock(Dispatcher::class)->shouldIgnoreMissing(),
            $this->settings,
            $this->logger,
        ])->makePartial()->shouldAllowMockingProtectedMethods();

        $mailer->shouldReceive('parseView')->andReturn(['html-content', 'text-content', null]);
        $mailer->shouldReceive('addContent')->andReturn(null);
        $mailer->shouldReceive('createMessage')->andReturn(m::mock(\Illuminate\Mail\Message::class)->shouldIgnoreMissing());
        $mailer->shouldReceive('shouldSendMessage')->andReturn(false); // skip actual transport

        $this->logger->shouldNotReceive('error');

        $mailer->send(['html' => 'view.html', 'text' => 'view.text'], [], function () {});
    }

    #[Test]
    public function failed_send_logs_structured_error_with_recipient_context(): void
    {
        $exception = new RuntimeException('SMTP connection refused');

        $mailer = m::mock(Mailer::class, [
            'flarum',
            m::mock(Factory::class)->shouldIgnoreMissing(),
            m::mock(TransportInterface::class),
            m::mock(Dispatcher::class)->shouldIgnoreMissing(),
            $this->settings,
            $this->logger,
        ])->makePartial()->shouldAllowMockingProtectedMethods();

        $mailer->shouldReceive('parseView')->andThrow($exception);

        $this->logger->shouldReceive('error')
            ->once()
            ->with(
                'Failed to send email.',
                m::on(function (array $context) use ($exception) {
                    return $context['recipient_email'] === 'user@example.com'
                        && $context['recipient_name'] === 'Jane Doe'
                        && $context['reason'] === $exception->getMessage()
                        && $context['exception_class'] === RuntimeException::class;
                })
            );

        $this->expectException(RuntimeException::class);

        $mailer->send(
            ['html' => 'view.html', 'text' => 'view.text'],
            ['userEmail' => 'user@example.com', 'username' => 'Jane Doe'],
            function () {}
        );
    }

    #[Test]
    public function failed_send_logs_with_null_context_when_data_keys_absent(): void
    {
        $exception = new RuntimeException('Transport error');

        $mailer = m::mock(Mailer::class, [
            'flarum',
            m::mock(Factory::class)->shouldIgnoreMissing(),
            m::mock(TransportInterface::class),
            m::mock(Dispatcher::class)->shouldIgnoreMissing(),
            $this->settings,
            $this->logger,
        ])->makePartial()->shouldAllowMockingProtectedMethods();

        $mailer->shouldReceive('parseView')->andThrow($exception);

        $this->logger->shouldReceive('error')
            ->once()
            ->with(
                'Failed to send email.',
                m::on(function (array $context) {
                    return $context['recipient_email'] === null
                        && $context['recipient_name'] === null;
                })
            );

        $this->expectException(RuntimeException::class);

        $mailer->send(['html' => 'view.html', 'text' => 'view.text'], [], function () {});
    }

    #[Test]
    public function failed_send_rethrows_original_exception(): void
    {
        $exception = new RuntimeException('Mailbox full');

        $mailer = m::mock(Mailer::class, [
            'flarum',
            m::mock(Factory::class)->shouldIgnoreMissing(),
            m::mock(TransportInterface::class),
            m::mock(Dispatcher::class)->shouldIgnoreMissing(),
            $this->settings,
            $this->logger,
        ])->makePartial()->shouldAllowMockingProtectedMethods();

        $mailer->shouldReceive('parseView')->andThrow($exception);
        $this->logger->shouldReceive('error')->once();

        try {
            $mailer->send(['html' => 'view.html', 'text' => 'view.text'], [], function () {});
            $this->fail('Expected exception was not thrown');
        } catch (RuntimeException $caught) {
            $this->assertSame($exception, $caught, 'The exact original exception instance must be re-thrown');
        }
    }
}
