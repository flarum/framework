<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Mail;

use Flarum\Mail\Event\EmailSendFailed;
use Flarum\Mail\Mailer;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\unit\TestCase;
use Flarum\User\User;
use Flarum\User\UserRepository;
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
    private UserRepository $users;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settings = m::mock(SettingsRepositoryInterface::class);
        $this->logger = m::mock(LoggerInterface::class);
        $this->users = m::mock(UserRepository::class);

        $this->settings->shouldReceive('get')->with('mail_format')->andReturn('multipart');
    }

    /**
     * Build a partial Mailer mock that stubs parent::send() internals so we
     * control whether an exception is thrown without needing a real transport.
     */
    private function makeMailer(?RuntimeException $throwOnParseView = null): Mailer
    {
        $mailer = m::mock(Mailer::class, [
            'flarum',
            m::mock(Factory::class)->shouldIgnoreMissing(),
            m::mock(TransportInterface::class),
            m::mock(Dispatcher::class)->shouldIgnoreMissing(),
            $this->settings,
            $this->logger,
            $this->users,
        ])->makePartial()->shouldAllowMockingProtectedMethods();

        if ($throwOnParseView) {
            $mailer->shouldReceive('parseView')->andThrow($throwOnParseView);
        } else {
            $mailer->shouldReceive('parseView')->andReturn(['html-content', 'text-content', null]);
            $mailer->shouldReceive('addContent')->andReturn(null);
            $mailer->shouldReceive('createMessage')->andReturn(m::mock(\Illuminate\Mail\Message::class)->shouldIgnoreMissing());
            $mailer->shouldReceive('shouldSendMessage')->andReturn(false);
        }

        return $mailer;
    }

    #[Test]
    public function successful_send_does_not_log_anything(): void
    {
        $this->logger->shouldNotReceive('error');

        $this->makeMailer()->send(['html' => 'view.html', 'text' => 'view.text'], [], function () {});
    }

    #[Test]
    public function failed_send_logs_structured_error_with_recipient_context(): void
    {
        $exception = new RuntimeException('SMTP connection refused');

        $this->users->shouldReceive('findByEmail')->with('user@example.com')->andReturn(null);

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

        $this->makeMailer($exception)->send(
            ['html' => 'view.html', 'text' => 'view.text'],
            ['userEmail' => 'user@example.com', 'username' => 'Jane Doe'],
            function () {}
        );
    }

    #[Test]
    public function failed_send_includes_user_model_when_email_matches_account(): void
    {
        $exception = new RuntimeException('Mailbox not found');
        $user = m::mock(User::class);

        $this->users->shouldReceive('findByEmail')->with('user@example.com')->andReturn($user);

        $dispatcher = m::mock(Dispatcher::class);
        $dispatcher->shouldReceive('until')->andReturn(null);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with(m::on(function (EmailSendFailed $event) use ($user) {
                return $event->recipient === $user
                    && $event->recipientEmail === 'user@example.com';
            }));

        $mailer = m::mock(Mailer::class, [
            'flarum',
            m::mock(Factory::class)->shouldIgnoreMissing(),
            m::mock(TransportInterface::class),
            $dispatcher,
            $this->settings,
            $this->logger,
            $this->users,
        ])->makePartial()->shouldAllowMockingProtectedMethods();

        $mailer->shouldReceive('parseView')->andThrow($exception);
        $this->logger->shouldReceive('error')->once();

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

        $this->users->shouldNotReceive('findByEmail');

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

        $this->makeMailer($exception)->send(['html' => 'view.html', 'text' => 'view.text'], [], function () {});
    }

    #[Test]
    public function failed_send_rethrows_original_exception(): void
    {
        $exception = new RuntimeException('Mailbox full');

        $this->users->shouldReceive('findByEmail')->andReturn(null);
        $this->logger->shouldReceive('error')->once();

        try {
            $this->makeMailer($exception)->send(['html' => 'view.html', 'text' => 'view.text'], ['userEmail' => 'x@x.com'], function () {});
            $this->fail('Expected exception was not thrown');
        } catch (RuntimeException $caught) {
            $this->assertSame($exception, $caught, 'The exact original exception instance must be re-thrown');
        }
    }
}
