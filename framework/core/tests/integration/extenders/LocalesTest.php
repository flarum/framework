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
use PHPUnit\Framework\Attributes\Test;

class LocalesTest extends TestCase
{
    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        array_map('unlink', glob($this->tmpDir().'/storage/locale/*'));
    }

    #[Test]
    public function custom_translation_does_not_exist_by_default()
    {
        $this->app()->getContainer()->make('flarum.locales');
        $translator = $this->app()->getContainer()->make(Translator::class);

        $this->assertEquals('test.hello', $translator->trans('test.hello', ['name' => 'ACME']));
    }

    #[Test]
    public function custom_translation_exists_if_added()
    {
        $this->extend(
            new Extend\Locales(dirname(__FILE__, 3).'/fixtures/locales')
        );

        $this->app()->getContainer()->make('flarum.locales');
        $translator = $this->app()->getContainer()->make(Translator::class);

        $this->assertEquals('World ACME', $translator->trans('test.hello', ['name' => 'ACME']));
    }

    #[Test]
    public function custom_translation_exists_if_added_with_intl_suffix()
    {
        $this->extend(
            new Extend\Locales(dirname(__FILE__, 3).'/fixtures/locales')
        );

        $this->app()->getContainer()->make('flarum.locales');
        $translator = $this->app()->getContainer()->make(Translator::class);

        $this->assertEquals('World-intl ACME', $translator->trans('test.hello-intl', ['name' => 'ACME']));
    }

    #[Test]
    public function messageformat_works_in_translations()
    {
        $this->extend(
            new Extend\Locales(dirname(__FILE__, 3).'/fixtures/locales')
        );

        $this->app()->getContainer()->make('flarum.locales');
        $translator = $this->app()->getContainer()->make(Translator::class);

        $this->assertEquals('ACME invites ACME2 and one other person to her party.', $translator->trans('test.party-invitation', ['gender_of_host' => 'female', 'host' => 'ACME', 'num_guests' => 2, 'guest' => 'ACME2']));
    }

    #[Test]
    public function laravel_interface_methods_work()
    {
        $this->extend(
            new Extend\Locales(dirname(__FILE__, 3).'/fixtures/locales')
        );

        $this->app()->getContainer()->make('flarum.locales');
        $translator = $this->app()->getContainer()->make(Translator::class);

        $args = ['gender_of_host' => 'female', 'host' => 'ACME', 'num_guests' => 2, 'guest' => 'ACME2'];

        $this->assertEquals('ACME invites ACME2 and one other person to her party.', $translator->get('test.party-invitation', $args));
        // Number doesn't matter
        $this->assertEquals('ACME invites ACME2 and one other person to her party.', $translator->choice('test.party-invitation', 2, $args));
        $this->assertEquals('ACME invites ACME2 and one other person to her party.', $translator->choice('test.party-invitation', 50, $args));
        $this->assertEquals('ACME invites ACME2 and one other person to her party.', $translator->choice('test.party-invitation', -1000, $args));
        $this->assertEquals('ACME invites ACME2 and one other person to her party.', $translator->choice('test.party-invitation', null, $args));
    }

    #[Test]
    public function in_debug_mode_an_edited_translation_file_is_picked_up_on_the_next_request()
    {
        $locales = $this->tmpDir().'/edited-locales';
        @mkdir($locales);
        file_put_contents($locales.'/en.yml', "test:\n  edited: alpha\n");

        $this->extend(new Extend\Locales($locales));
        $container = $this->app()->getContainer();

        try {
            $container->make('flarum.locales');
            $this->assertEquals('alpha', $container->make(Translator::class)->trans('test.edited'));

            file_put_contents($locales.'/en.yml', "test:\n  edited: beta\n");
            // Later than the compiled catalogue, whatever the mtime resolution.
            touch($locales.'/en.yml', time() + 5);
            clearstatcache();

            // The next request builds its own translator and manager.
            $container->forgetInstance('translator');
            $container->forgetInstance(LocaleManager::class);
            $container->make(LocaleManager::class);

            $this->assertEquals('beta', $container->make(Translator::class)->trans('test.edited'));
        } finally {
            unlink($locales.'/en.yml');
            rmdir($locales);
        }
    }
}
