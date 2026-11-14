<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Locale;

use Flarum\Locale\CatalogueCacheFactory;
use Flarum\Locale\PrefixedYamlFileLoader;
use Flarum\Locale\Translator;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * One translator per request, built the way LocaleServiceProvider builds it,
 * sharing a cache directory across requests.
 */
class CatalogueFreshnessTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir().'/flarum-catalogue-freshness-'.bin2hex(random_bytes(4));
        mkdir($this->root.'/cache', 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->root.'/cache/*') ?: [] as $file) {
            unlink($file);
        }

        foreach (glob($this->root.'/*.yml') ?: [] as $file) {
            unlink($file);
        }

        rmdir($this->root.'/cache');
        rmdir($this->root);

        parent::tearDown();
    }

    private function request(bool $debug): string
    {
        $translator = new Translator('en', null, $this->root.'/cache', $debug);
        $translator->addLoader('prefixed_yaml', new PrefixedYamlFileLoader());
        $translator->setConfigCacheFactory(new CatalogueCacheFactory(fn () => 'same-revision', $debug));
        $translator->addResource('prefixed_yaml', ['file' => $this->root.'/en.yml', 'prefix' => null], 'en', 'messages+intl-icu');

        return $translator->trans('greeting');
    }

    private function editTranslations(string $yaml): void
    {
        file_put_contents($this->root.'/en.yml', $yaml);
        // Later than the catalogue, whatever the filesystem's mtime resolution.
        touch($this->root.'/en.yml', time() + 5);
        clearstatcache();
    }

    #[Test]
    public function in_debug_mode_an_edited_translation_file_is_picked_up_on_the_next_request()
    {
        $this->editTranslations("greeting: alpha\n");
        $this->assertSame('alpha', $this->request(true));

        $this->editTranslations("greeting: beta\n");
        $this->assertSame('beta', $this->request(true));
    }
}
