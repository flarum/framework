<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\mail;

use Flarum\Database\AbstractModel;
use Flarum\Extend;
use Flarum\Http\UrlGenerator;
use Flarum\Locale\TranslatorInterface;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\MailableInterface;
use Flarum\Notification\NotificationMailer;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\Transport\ArrayTransport;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mime\Email;

class NotificationFooterTypeTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    private ArrayTransport $transport;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extend(new Extend\Locales(dirname(__FILE__, 3).'/fixtures/notification-type-locales'));

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                [
                    'id' => 3,
                    'username' => 'german',
                    'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim',
                    'email' => 'german@machine.local',
                    'is_email_confirmed' => 1,
                    'preferences' => json_encode(['locale' => 'de']),
                ],
            ],
        ]);
    }

    private function sendNotification(BlueprintInterface&MailableInterface $blueprint, int $userId): Email
    {
        $container = $this->app()->getContainer();
        $container->make('flarum.locales');

        $this->transport = new ArrayTransport();
        $view = $container->make(Factory::class);
        $mailer = new Mailer('array', $view, $this->transport, $container->make(Dispatcher::class));
        $mailer->alwaysFrom('forum@machine.local');

        (new NotificationMailer(
            $mailer,
            $container->make(TranslatorInterface::class),
            $container->make(SettingsRepositoryInterface::class),
            $container->make(UrlGenerator::class),
            $view,
        ))->send($blueprint, User::find($userId));

        $messages = $this->transport->messages();
        $this->assertCount(1, $messages);

        return $messages->first()->getOriginalMessage();
    }

    #[Test]
    public function footer_names_a_type_with_a_translated_name(): void
    {
        $email = $this->sendNotification(new FooterNamedBlueprint(), 2);

        $this->assertStringContainsString('Replies to your test posts', $email->getHtmlBody());
        $this->assertStringContainsString('Replies to your test posts', $email->getTextBody());
        $this->assertStringNotContainsString('footerNamed', $email->getHtmlBody());
        $this->assertStringNotContainsString('footerNamed', $email->getTextBody());
    }

    #[Test]
    public function footer_names_the_type_in_the_recipients_locale(): void
    {
        $email = $this->sendNotification(new FooterNamedBlueprint(), 3);

        $this->assertStringContainsString('Antworten auf deine Testbeiträge', $email->getTextBody());
        $this->assertStringNotContainsString('footerNamed', $email->getTextBody());
    }

    #[Test]
    public function footer_leaves_out_a_type_without_a_translated_name(): void
    {
        $email = $this->sendNotification(new FooterUnnamedBlueprint(), 2);

        $this->assertStringContainsString('because email notifications are turned on for your account', $email->getHtmlBody());
        $this->assertStringContainsString('because email notifications are turned on for your account', $email->getTextBody());
        $this->assertStringNotContainsString('footerUnnamed', $email->getHtmlBody());
        $this->assertStringNotContainsString('footerUnnamed', $email->getTextBody());
        $this->assertStringNotContainsString('core.email.notification.types', $email->getTextBody());
    }
}

class FooterNamedBlueprint implements BlueprintInterface, MailableInterface
{
    public function getFromUser(): ?User
    {
        return null;
    }

    public function getSubject(): ?AbstractModel
    {
        return null;
    }

    public function getData(): ?array
    {
        return null;
    }

    public static function getType(): string
    {
        return 'footerNamed';
    }

    public static function getSubjectModel(): string
    {
        return User::class;
    }

    public function getEmailViews(): array
    {
        return ['text' => 'mail::plain.notification', 'html' => 'mail::html.notification'];
    }

    public function getEmailSubject(TranslatorInterface $translator): string
    {
        return 'Footer test';
    }
}

class FooterUnnamedBlueprint extends FooterNamedBlueprint
{
    public static function getType(): string
    {
        return 'footerUnnamed';
    }
}
