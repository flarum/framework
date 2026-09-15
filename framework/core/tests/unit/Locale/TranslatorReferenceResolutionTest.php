<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Locale;

use Flarum\Locale\PrefixedYamlFileLoader;
use Flarum\Locale\Translator;
use Flarum\Testing\unit\TestCase;
use Symfony\Component\Translation\MessageCatalogueInterface;

class TranslatorReferenceResolutionTest extends TestCase
{
    private const DOMAIN = 'messages'.MessageCatalogueInterface::INTL_DOMAIN_SUFFIX;

    /**
     * @var string
     */
    private $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sys_get_temp_dir().'/flarum-translator-test-'.uniqid('', true);
        mkdir($this->cacheDir);

        file_put_contents($this->cacheDir.'/en.yml', "foo: '=> bar'\nbar: Resolved\n");
        file_put_contents($this->cacheDir.'/de.yml', "baz: Beispiel\n");
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->cacheDir.'/*') ?: []);
        rmdir($this->cacheDir);

        parent::tearDown();
    }

    private function translator(): Translator
    {
        // Wired like LocaleServiceProvider: non-debug, cache dir, 'en' fallback.
        $translator = new Translator('de', null, $this->cacheDir, false);
        $translator->setFallbackLocales(['en']);
        $translator->addLoader('prefixed_yaml', new PrefixedYamlFileLoader());
        $translator->addResource('prefixed_yaml', ['file' => $this->cacheDir.'/en.yml', 'prefix' => ''], 'en', self::DOMAIN);
        $translator->addResource('prefixed_yaml', ['file' => $this->cacheDir.'/de.yml', 'prefix' => ''], 'de', self::DOMAIN);

        return $translator;
    }

    public function test_references_are_resolved_when_locale_is_loaded_directly()
    {
        $translator = $this->translator();

        $this->assertSame('Resolved', $translator->getCatalogue('en')->get('foo', 'messages'));
    }

    public function test_references_are_resolved_when_locale_was_first_loaded_as_a_fallback()
    {
        $translator = $this->translator();

        // Loading 'de' makes Symfony implicitly load and store the 'en'
        // catalogue as its fallback, before 'en' is ever requested directly.
        $translator->getCatalogue('de');

        $this->assertSame('Resolved', $translator->getCatalogue('en')->get('foo', 'messages'));
    }

    public function test_references_are_resolved_in_fallback_catalogues()
    {
        $translator = $this->translator();

        $fallback = $translator->getCatalogue('de')->getFallbackCatalogue();

        $this->assertSame('Resolved', $fallback->get('foo', 'messages'));
        $this->assertSame('Resolved', $translator->trans('foo', [], null, 'de'));
    }
}
