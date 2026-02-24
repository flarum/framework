<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Notification;

use Flarum\Http\RouteCollectionUrlGenerator;
use Flarum\Http\UrlGenerator;
use Flarum\Locale\TranslatorInterface;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\MailableInterface;
use Flarum\Notification\NotificationMailer;
use Flarum\Notification\UnsubscribeToken;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\unit\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\View\Factory;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

class NotificationMailerTest extends TestCase
{
    private Mailer $mailer;
    private TranslatorInterface $translator;
    private SettingsRepositoryInterface $settings;
    private UrlGenerator $url;
    private Factory $view;
    private NotificationMailer $notificationMailer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mailer = m::mock(Mailer::class);
        $this->translator = m::mock(TranslatorInterface::class);
        $this->settings = m::mock(SettingsRepositoryInterface::class);
        $this->url = m::mock(UrlGenerator::class);
        $this->view = m::mock(Factory::class);

        // Common stub setup
        $this->translator->shouldReceive('setLocale')->once();
        $this->settings->shouldReceive('get')->with('default_locale')->andReturn('en');
        $this->settings->shouldReceive('get')->with('forum_title')->andReturn('Test Forum');

        $routeGenerator = m::mock(RouteCollectionUrlGenerator::class);
        $routeGenerator->shouldReceive('route')->andReturn('https://example.com/some-url');
        $this->url->shouldReceive('to')->andReturn($routeGenerator);

        $this->view->shouldReceive('share')->once();

        // Use a testable subclass that stubs out the DB-touching unsubscribe token
        $this->notificationMailer = new class($this->mailer, $this->translator, $this->settings, $this->url, $this->view) extends NotificationMailer {
            protected function generateUnsubscribeToken(int $userId, string $emailType): UnsubscribeToken
            {
                $token = m::mock(UnsubscribeToken::class)->shouldIgnoreMissing();
                $token->shouldReceive('save')->once();
                $token->token = 'fake-token';

                return $token;
            }
        };
    }

    #[Test]
    public function successful_send_delegates_to_mailer(): void
    {
        $this->mailer->shouldReceive('send')->once();

        $this->notificationMailer->send($this->makeBlueprint(), $this->makeUser());
    }

    #[Test]
    public function mailer_exception_propagates_to_caller(): void
    {
        $exception = new RuntimeException('Connection refused by SMTP server');

        $this->mailer->shouldReceive('send')->once()->andThrow($exception);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Connection refused by SMTP server');

        $this->notificationMailer->send($this->makeBlueprint(), $this->makeUser());
    }

    private function makeBlueprint(): MailableInterface&BlueprintInterface
    {
        $blueprint = m::mock(MailableInterface::class, BlueprintInterface::class);
        $blueprint->shouldReceive('getEmailViews')->andReturn(['text' => 'emails.test', 'html' => 'emails.test-html']);
        $blueprint->shouldReceive('getEmailSubject')->andReturn('Test Subject');
        $blueprint->allows('getType')->andReturn('testNotification');

        return $blueprint;
    }

    private function makeUser(): User
    {
        $user = m::mock(User::class)->shouldIgnoreMissing();
        $user->shouldReceive('getAttribute')->with('id')->andReturn(42);
        $user->shouldReceive('getAttribute')->with('email')->andReturn('user@example.com');
        $user->shouldReceive('getAttribute')->with('display_name')->andReturn('Test User');
        $user->shouldReceive('getPreference')->with('locale')->andReturn(null);

        return $user;
    }
}
