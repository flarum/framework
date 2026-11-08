<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Locale;

use Flarum\Locale\Translator;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\MessageCatalogueInterface;

class TranslatorFallbackTest extends TestCase
{
    #[Test]
    #[DataProvider('fallbacks')]
    public function translations_follow_the_language_fallback_order(string $default, string $active, string $key, string $expected): void
    {
        $translator = new Translator($default);
        $translator->setDefaultFallbackLocale($default);
        $translator->addLoader('array', new ArrayLoader());

        foreach ([
            'en' => ['shared' => 'English', 'default' => 'English default', 'parent' => 'English parent', 'english' => 'English only'],
            'de' => ['shared' => 'Deutsch', 'default' => 'German default', 'parent' => 'German parent', 'reference' => 'German target'],
            'de-CH' => ['default' => 'Swiss default', 'alias' => '=> reference'],
            'de_CH' => ['default' => 'Swiss default'],
            'fr' => ['shared' => 'Français', 'parent' => 'French parent'],
            'fr-CA' => ['shared' => 'Québécois'],
            'es' => ['shared' => 'Generic Spanish'],
            'es_419' => ['shared' => 'Latin American Spanish'],
        ] as $locale => $messages) {
            $translator->addResource('array', $messages, $locale, 'messages'.MessageCatalogueInterface::INTL_DOMAIN_SUFFIX);
        }

        $this->assertSame($expected, $translator->trans($key, [], null, $active));
    }

    public static function fallbacks(): array
    {
        return [
            'selected locale wins' => ['de-CH', 'fr-CA', 'shared', 'Québécois'],
            'selected parent precedes default' => ['de-CH', 'fr-CA', 'parent', 'French parent'],
            'default precedes its parent' => ['de-CH', 'fr', 'default', 'Swiss default'],
            'default parent precedes English' => ['de-CH', 'fr', 'reference', 'German target'],
            'English is the last resort' => ['de-CH', 'fr', 'english', 'English only'],
            'English default' => ['en', 'fr', 'default', 'English default'],
            'selected equals default' => ['de-CH', 'de-CH', 'parent', 'German parent'],
            'selected equals default parent' => ['de-CH', 'de', 'default', 'German default'],
            'selected parent equals default' => ['fr', 'fr-CA', 'parent', 'French parent'],
            'underscore regional locale' => ['de_CH', 'fr', 'default', 'Swiss default'],
            'Symfony parent mapping' => ['es_AR', 'fr', 'shared', 'Français'],
            'hyphenated locale follows existing Symfony parsing' => ['es-AR', 'it', 'shared', 'Generic Spanish'],
            'Symfony parent mapping for missing locale' => ['es_AR', 'it', 'shared', 'Latin American Spanish'],
            'reference when selected locale is the default parent' => ['de-CH', 'de', 'alias', 'German target'],
            'reference when selected locale shares the default parent' => ['de-CH', 'de-AT', 'alias', 'German target'],
            'reference crosses default and parent' => ['de-CH', 'fr', 'alias', 'German target'],
            'missing everywhere' => ['de-CH', 'fr', 'absent', 'absent'],
        ];
    }

    #[Test]
    public function changing_the_fallback_replaces_the_previous_chain(): void
    {
        $translator = new Translator('it');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', ['hello' => 'Hallo'], 'de');
        $translator->addResource('array', ['hello' => 'Hello'], 'en');
        $translator->setDefaultFallbackLocale('de-CH');
        $this->assertSame('Hallo', $translator->trans('hello'));

        $translator->setDefaultFallbackLocale('en');
        $this->assertSame('Hello', $translator->trans('hello'));
    }

    #[Test]
    public function an_empty_default_uses_english_with_a_compiled_catalogue(): void
    {
        $cache = sys_get_temp_dir().'/flarum-empty-fallback-'.uniqid('', true);
        mkdir($cache);

        try {
            for ($request = 0; $request < 2; $request++) {
                $translator = new Translator('fr', null, $cache, false);
                $translator->addLoader('array', new ArrayLoader());
                $translator->addResource('array', ['hello' => 'Hello'], 'en');
                $translator->setDefaultFallbackLocale('');

                $this->assertSame('Hello', $translator->trans('hello'));
            }
        } finally {
            array_map('unlink', glob($cache.'/*') ?: []);
            rmdir($cache);
        }
    }

}
