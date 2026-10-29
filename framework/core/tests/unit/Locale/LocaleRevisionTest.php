<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Locale;

use Flarum\Locale\LocaleManager;
use Flarum\Locale\Translator;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\unit\TestCase;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;

/**
 * A compiled catalogue's filename is derived from the fallback locales alone,
 * and in production `ConfigCache::isFresh()` answers `is_file()` — so nothing
 * detects that the translations behind it have changed. Enabling an extension
 * on one instance leaves every other instance serving a catalogue that predates
 * it, indefinitely, with no signal that would ever refresh it.
 *
 * The revision below is that signal: it is computed from what an instance can
 * see for itself, so an instance that has never been told anything still
 * notices its catalogue is stale.
 */
class LocaleRevisionTest extends TestCase
{
    private function settings(array $rows = []): SettingsRepositoryInterface
    {
        return new class($rows) implements SettingsRepositoryInterface {
            public function __construct(public array $rows)
            {
            }

            public function all(): array
            {
                return $this->rows;
            }

            public function get(string $key, mixed $default = null): mixed
            {
                return $this->rows[$key] ?? $default;
            }

            public function set(string $key, mixed $value): void
            {
                $this->rows[$key] = $value;
            }

            public function delete(string $keyLike): void
            {
                unset($this->rows[$keyLike]);
            }
        };
    }

    private function manager(?SettingsRepositoryInterface $settings = null): LocaleManager
    {
        $translator = m::mock(Translator::class);
        $translator->shouldReceive('addResource');

        return new LocaleManager($translator, null, $settings ?? $this->settings());
    }

    #[Test]
    public function the_revision_is_stable_while_nothing_changes()
    {
        $locales = $this->manager();
        $locales->addTranslations('en', '/core/locale/core.yml');

        $first = $locales->revision();

        $this->assertSame($first, $locales->revision());
        $this->assertNotSame('', $first);
    }

    /**
     * The case that matters: enabling an extension adds its locale files, so an
     * instance that merely boots afterwards computes a different revision — no
     * message from the instance that performed the toggle required.
     */
    #[Test]
    public function adding_a_translation_file_changes_the_revision()
    {
        $before = $this->manager();
        $before->addTranslations('en', '/core/locale/core.yml');

        $after = $this->manager();
        $after->addTranslations('en', '/core/locale/core.yml');
        $after->addTranslations('en', '/ext/flarum-tags/locale/en.yml');

        $this->assertNotSame($before->revision(), $after->revision());
    }

    #[Test]
    public function removing_a_translation_file_changes_the_revision()
    {
        $with = $this->manager();
        $with->addTranslations('en', '/core/locale/core.yml');
        $with->addTranslations('en', '/ext/flarum-tags/locale/en.yml');

        $without = $this->manager();
        $without->addTranslations('en', '/core/locale/core.yml');

        $this->assertNotSame($with->revision(), $without->revision());
    }

    #[Test]
    public function the_revision_ignores_the_order_files_were_registered_in()
    {
        $one = $this->manager();
        $one->addTranslations('en', '/a.yml');
        $one->addTranslations('en', '/b.yml');

        $two = $this->manager();
        $two->addTranslations('en', '/b.yml');
        $two->addTranslations('en', '/a.yml');

        $this->assertSame($one->revision(), $two->revision());
    }

    #[Test]
    public function a_files_locale_is_part_of_the_revision()
    {
        $en = $this->manager();
        $en->addTranslations('en', '/locale/x.yml');

        $de = $this->manager();
        $de->addTranslations('de', '/locale/x.yml');

        $this->assertNotSame($en->revision(), $de->revision());
    }

    /**
     * A language pack registers the same paths under a module prefix, which
     * changes which keys the catalogue ends up with.
     */
    #[Test]
    public function a_modules_prefix_is_part_of_the_revision()
    {
        $bare = $this->manager();
        $bare->addTranslations('en', '/locale/x.yml');

        $prefixed = $this->manager();
        $prefixed->addTranslations('en', '/locale/x.yml', 'flarum-tags');

        $this->assertNotSame($bare->revision(), $prefixed->revision());
    }

    /**
     * Translations that live somewhere other than a registered file — the
     * database, in fof/linguist's case — cannot show up in the file list. A
     * stamp any extension can bump covers them, without core having to ask a
     * resource whether it is fresh: doing that would run third-party code that
     * has never executed in production, some of which resolves services and
     * queries the database.
     */
    #[Test]
    public function bumping_the_stamp_changes_the_revision()
    {
        $settings = $this->settings();

        $locales = $this->manager($settings);
        $locales->addTranslations('en', '/core/locale/core.yml');

        $before = $locales->revision();

        $settings->set(LocaleManager::REVISION_STAMP_KEY, '1757400000');

        $this->assertNotSame($before, $this->managerWith($settings)->revision());
    }

    #[Test]
    public function two_instances_with_the_same_files_and_stamp_agree()
    {
        $settings = $this->settings([LocaleManager::REVISION_STAMP_KEY => '1757400000']);

        $podA = $this->manager($settings);
        $podA->addTranslations('en', '/core/locale/core.yml');
        $podA->addTranslations('de', '/pack/de.yml');

        $podB = $this->manager($settings);
        $podB->addTranslations('de', '/pack/de.yml');
        $podB->addTranslations('en', '/core/locale/core.yml');

        $this->assertSame(
            $podA->revision(),
            $podB->revision(),
            'instances seeing the same translations must agree, or they would rebuild forever'
        );
    }

    #[Test]
    public function the_revision_works_without_a_settings_repository()
    {
        $translator = m::mock(Translator::class);
        $translator->shouldReceive('addResource');

        $locales = new LocaleManager($translator, null);
        $locales->addTranslations('en', '/core/locale/core.yml');

        $this->assertNotSame('', $locales->revision());
    }

    private function managerWith(SettingsRepositoryInterface $settings): LocaleManager
    {
        $locales = $this->manager($settings);
        $locales->addTranslations('en', '/core/locale/core.yml');

        return $locales;
    }
}
