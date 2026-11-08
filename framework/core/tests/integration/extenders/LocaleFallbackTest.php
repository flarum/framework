<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Locale\LocaleManager;
use Flarum\Locale\Translator;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class LocaleFallbackTest extends TestCase
{
    #[Test]
    public function missing_translations_use_the_forum_default_language(): void
    {
        $this->setting('default_locale', 'de');
        $this->extend(new Extend\Locales(__DIR__.'/../../fixtures/locale-fallback'));
        $container = $this->app()->getContainer();
        $container->make(LocaleManager::class);

        $this->assertSame('Hallo', $container->make(Translator::class)->trans('fallback.forum.greeting', [], null, 'fr'));
    }

    #[Test]
    public function missing_translations_use_the_parent_of_the_forum_default_language(): void
    {
        $this->setting('default_locale', 'de-CH');
        $this->extend(new Extend\Locales(__DIR__.'/../../fixtures/locale-fallback'));
        $container = $this->app()->getContainer();
        $container->make(LocaleManager::class);

        $this->assertSame('Hallo', $container->make(Translator::class)->trans('fallback.forum.greeting', [], null, 'fr'));
    }

    #[Test]
    #[DataProvider('frontends')]
    public function saving_the_default_language_updates_a_cached_frontend_bundle_outside_debug_mode(string $frontend, string $path): void
    {
        $this->config('debug', false);
        $this->setting('default_locale', 'de');
        $this->extend(new Extend\Locales(__DIR__.'/../../fixtures/locale-fallback'));
        $container = $this->app()->getContainer();
        $container->make(LocaleManager::class)->addLocale('fr', 'French');

        $admin = User::findOrFail(1);
        $admin->setPreference('locale', 'fr');
        $admin->save();

        $before = $this->send($this->request('GET', $path, ['authenticatedAs' => 1]));
        $this->assertSame(200, $before->getStatusCode());
        preg_match('~src="([^"]*/'.$frontend.'-fr\.js[^"]*)"~', (string) $before->getBody(), $beforeBundle);
        $this->assertNotEmpty($beforeBundle);
        $disk = $container->make('filesystem')->disk('flarum-assets');
        $beforeFile = basename(parse_url(html_entity_decode($beforeBundle[1]), PHP_URL_PATH));
        $this->assertStringContainsString('"fallback.'.$frontend.'.greeting":"Hallo"', $disk->get($beforeFile));

        $saved = $this->send($this->request('POST', '/api/settings', [
            'authenticatedAs' => 1,
            'json' => ['default_locale' => 'nl'],
        ]));
        $this->assertSame(204, $saved->getStatusCode());

        // A subsequent request resolves its translator from the saved setting.
        $container->forgetInstance('translator');
        $container->forgetInstance(LocaleManager::class);
        $container->make(LocaleManager::class)->addLocale('fr', 'French');

        $after = $this->send($this->request('GET', $path, ['authenticatedAs' => 1]));
        $this->assertSame(200, $after->getStatusCode());
        preg_match('~src="([^"]*/'.$frontend.'-fr\.js[^"]*)"~', (string) $after->getBody(), $afterBundle);
        $this->assertNotEmpty($afterBundle);
        $this->assertNotSame($beforeBundle[1], $afterBundle[1]);
        $afterFile = basename(parse_url(html_entity_decode($afterBundle[1]), PHP_URL_PATH));
        $this->assertStringContainsString('"fallback.'.$frontend.'.greeting":"Goedendag"', $disk->get($afterFile));
    }

    public static function frontends(): array
    {
        return [
            'forum' => ['forum', '/'],
            'admin' => ['admin', '/admin'],
        ];
    }
}
