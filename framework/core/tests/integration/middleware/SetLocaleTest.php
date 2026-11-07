<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\middleware;

use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use Flarum\Locale\LocaleManager;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use PHPUnit\Framework\Attributes\Test;

class SetLocaleTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extend(new class implements ExtenderInterface {
            public function extend(Container $container, ?Extension $extension = null): void
            {
                $container->resolving(LocaleManager::class, function (LocaleManager $locales) {
                    $locales->addLocale('fr', 'Français');
                });
            }
        });

        $this->prepareDatabase([
            User::class => [
                $this->normalUser() + [
                    'preferences' => json_encode(['locale' => 'fr']),
                ],
            ],
        ]);
    }

    private function htmlLang(array $options = [], array $cookies = []): ?string
    {
        $request = $this->request('GET', '/', $options);

        if ($cookies) {
            $request = $request->withCookieParams($request->getCookieParams() + $cookies);
        }

        $html = $this->send($request)->getBody()->getContents();

        preg_match('/<html[^>]* lang="([^"]+)"/', $html, $matches);

        return $matches[1] ?? null;
    }

    #[Test]
    public function guest_locale_cookie_is_used_when_language_selector_is_shown()
    {
        $this->assertEquals('fr', $this->htmlLang([], ['locale' => 'fr']));
    }

    #[Test]
    public function guest_locale_cookie_is_ignored_when_language_selector_is_hidden()
    {
        $this->setting('show_language_selector', '0');

        $this->assertEquals('en', $this->htmlLang([], ['locale' => 'fr']));
    }

    #[Test]
    public function user_locale_preference_is_used_when_language_selector_is_shown()
    {
        $this->assertEquals('fr', $this->htmlLang(['authenticatedAs' => 2]));
    }

    #[Test]
    public function user_locale_preference_is_ignored_when_language_selector_is_hidden()
    {
        $this->setting('show_language_selector', '0');

        $this->assertEquals('en', $this->htmlLang(['authenticatedAs' => 2]));
    }
}
