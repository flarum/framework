<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Event\Disabling;
use Flarum\Extension\Extension;
use Flarum\Locale\LocaleManager;
use Flarum\Testing\integration\TestCase;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

class LanguagePackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        array_map('unlink', glob($this->tmpDir().'/storage/locale/*'));
    }

    private function packExtension(array $extra = [], string $name = 'acme/lang-arabic'): Extension
    {
        return new Extension(dirname(__FILE__, 3).'/fixtures/language-pack', [
            'name' => $name,
            'extra' => $extra,
        ]);
    }

    private function languagePack(Extend\LanguagePack $pack, array $extra = [], string $name = 'acme/lang-arabic'): void
    {
        $extension = $this->packExtension($extra, $name);

        $this->extend(new class($pack, $extension) implements ExtenderInterface {
            public function __construct(
                private readonly Extend\LanguagePack $pack,
                private readonly Extension $extension
            ) {
            }

            public function extend(Container $container, ?Extension $extension = null): void
            {
                $this->pack->extend($container, $this->extension);
            }
        });
    }

    private function html(): string
    {
        return $this->send($this->request('GET', '/'))->getBody()->getContents();
    }

    private function htmlTag(): string
    {
        preg_match('/<html[^>]*>/', $this->html(), $matches);

        return $matches[0] ?? '';
    }

    private function payloadLocales(): array
    {
        preg_match('#<script id="flarum-json-payload" type="application/json">(.*?)</script>#s', $this->html(), $matches);

        return json_decode($matches[1] ?? '{}', true)['locales'] ?? [];
    }

    #[Test]
    public function a_right_to_left_pack_sets_the_document_direction()
    {
        $this->setting('default_locale', 'ar');
        $this->languagePack(
            (new Extend\LanguagePack())->direction('rtl'),
            ['flarum-locale' => ['code' => 'ar', 'title' => 'العربية']]
        );

        $this->assertStringContainsString('dir="rtl"', $this->htmlTag());
    }

    #[Test]
    public function a_pack_that_does_not_set_a_direction_is_left_to_right()
    {
        $this->setting('default_locale', 'ar');
        $this->languagePack(
            new Extend\LanguagePack(),
            ['flarum-locale' => ['code' => 'ar', 'title' => 'العربية']]
        );

        $this->assertStringContainsString('dir="ltr"', $this->htmlTag());
    }

    #[Test]
    public function a_direction_other_than_ltr_or_rtl_is_refused()
    {
        $this->expectException(InvalidArgumentException::class);

        (new Extend\LanguagePack())->direction('auto');
    }

    #[Test]
    public function a_regional_locale_takes_the_direction_of_its_language()
    {
        $this->setting('default_locale', 'ar-EG');
        $this->languagePack(
            (new Extend\LanguagePack())->direction('rtl'),
            ['flarum-locale' => ['code' => 'ar', 'title' => 'العربية']]
        );
        $this->languagePack(
            new Extend\LanguagePack(),
            ['flarum-locale' => ['code' => 'ar-EG', 'title' => 'العربية (مصر)']],
            'acme/lang-arabic-egypt'
        );

        $this->assertStringContainsString('dir="rtl"', $this->htmlTag());
    }

    #[Test]
    public function a_regional_locale_that_sets_its_own_direction_keeps_it()
    {
        $this->setting('default_locale', 'pa-Arab');
        $this->languagePack(
            (new Extend\LanguagePack())->direction('ltr'),
            ['flarum-locale' => ['code' => 'pa', 'title' => 'ਪੰਜਾਬੀ']],
            'acme/lang-punjabi'
        );
        $this->languagePack(
            (new Extend\LanguagePack())->direction('rtl'),
            ['flarum-locale' => ['code' => 'pa-Arab', 'title' => 'پنجابی']],
            'acme/lang-punjabi-shahmukhi'
        );

        $this->assertStringContainsString('dir="rtl"', $this->htmlTag());
    }

    #[Test]
    public function a_pack_can_name_its_locale_on_the_extender()
    {
        $this->languagePack(
            (new Extend\LanguagePack())->code('ar')->title('العربية')
        );

        $this->assertSame('العربية', $this->payloadLocales()['ar'] ?? null);
    }

    #[Test]
    public function the_extender_takes_precedence_over_composer_json()
    {
        $this->languagePack(
            (new Extend\LanguagePack())->code('ar')->title('العربية'),
            ['flarum-locale' => ['code' => 'ar-old', 'title' => 'Arabic (old)']]
        );

        $this->assertSame(['ar' => 'العربية'], array_diff_key($this->payloadLocales(), ['en' => true]));
    }

    #[Test]
    public function a_pack_with_no_code_or_title_is_refused()
    {
        $this->expectException(RuntimeException::class);

        (new Extend\LanguagePack())->extend(new IlluminateContainer(), $this->packExtension());
    }

    #[Test]
    public function the_default_language_pack_cannot_be_disabled_when_named_on_the_extender()
    {
        $this->setting('default_locale', 'ar');
        $this->languagePack((new Extend\LanguagePack())->code('ar')->title('العربية'));

        $this->expectException(PermissionDeniedException::class);

        $this->app()->getContainer()->make(Dispatcher::class)->dispatch(new Disabling($this->packExtension()));
    }

    #[Test]
    public function another_language_pack_can_be_disabled_when_named_on_the_extender()
    {
        $this->setting('default_locale', 'en');
        $this->languagePack((new Extend\LanguagePack())->code('ar')->title('العربية'));

        $this->app()->getContainer()->make(Dispatcher::class)->dispatch(new Disabling($this->packExtension()));

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function an_email_in_a_right_to_left_language_is_right_to_left()
    {
        $this->languagePack(
            (new Extend\LanguagePack())->code('ar')->title('العربية')->direction('rtl')
        );

        $container = $this->app()->getContainer();
        $container->make(LocaleManager::class)->setLocale('ar');

        $data = [
            'user' => (object) ['email' => 'recipient@example.com'],
            'unsubscribeLink' => 'https://example.com/unsubscribe',
            'settingsLink' => 'https://example.com/settings',
            'type' => 'testNotification',
            'forumTitle' => 'Test Forum',
            'username' => 'Recipient',
            'userEmail' => 'recipient@example.com',
            'body' => 'The notification body.',
            'title' => 'Notification',
        ];

        $view = $container->make(Factory::class);
        $view->share($data);
        $html = $view->make('mail::html.notification', $data)->render();

        preg_match('/<html[^>]*>/', $html, $matches);

        $this->assertStringContainsString('dir="rtl"', $matches[0] ?? '');
    }

    #[Test]
    public function a_pack_can_set_its_direction_in_composer_json()
    {
        $this->setting('default_locale', 'ar');
        $this->languagePack(
            new Extend\LanguagePack(),
            ['flarum-locale' => ['code' => 'ar', 'title' => 'العربية', 'direction' => 'rtl']]
        );

        $this->assertStringContainsString('dir="rtl"', $this->htmlTag());
    }

    #[Test]
    public function the_extender_direction_takes_precedence_over_composer_json()
    {
        $this->setting('default_locale', 'ar');
        $this->languagePack(
            (new Extend\LanguagePack())->direction('ltr'),
            ['flarum-locale' => ['code' => 'ar', 'title' => 'العربية', 'direction' => 'rtl']]
        );

        $this->assertStringContainsString('dir="ltr"', $this->htmlTag());
    }

    #[Test]
    public function a_direction_in_composer_json_other_than_ltr_or_rtl_is_refused()
    {
        $this->expectException(InvalidArgumentException::class);

        (new Extend\LanguagePack())->extend(
            new IlluminateContainer(),
            $this->packExtension(['flarum-locale' => ['code' => 'ar', 'title' => 'العربية', 'direction' => 'auto']])
        );
    }
}
