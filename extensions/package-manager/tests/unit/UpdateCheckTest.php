<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Work\UpdateCheck;
use PHPUnit\Framework\TestCase;

/**
 * 🚨 The rule: this produces a HINT, never a promise.
 *
 * "3.6.0 exists" and "you can have 3.6.0" are different questions, and only a
 * resolve answers the second. Conflating them is why the current tooling's
 * update badges are so often wrong — and a badge people learn to distrust is
 * worse than no badge.
 */
class UpdateCheckTest extends TestCase
{
    private string $cache;

    protected function setUp(): void
    {
        $this->cache = sys_get_temp_dir().'/mw-upd-'.bin2hex(random_bytes(6)).'.json';
    }

    protected function tearDown(): void
    {
        @unlink($this->cache);
    }

    private function check(): UpdateCheck
    {
        return new UpdateCheck($this->cache);
    }

    private function feed(array $map): callable
    {
        return fn (string $name) => $map[$name] ?? null;
    }

    public function test_it_finds_a_newer_stable_release(): void
    {
        $result = $this->check()->refresh(
            ['fof/pwa' => '2.0.0-beta.3'],
            $this->feed(['fof/pwa' => ['2.0.0-beta.3', '2.0.0-beta.4']])
        );

        $this->assertSame(['from' => '2.0.0-beta.3', 'to' => '2.0.0-beta.4'], $result['updates']['fof/pwa']);
    }

    public function test_an_up_to_date_package_is_not_reported(): void
    {
        $result = $this->check()->refresh(
            ['a/b' => '2.0.0'],
            $this->feed(['a/b' => ['1.0.0', '2.0.0']])
        );

        $this->assertSame([], $result['updates']);
    }

    public function test_a_v_prefix_does_not_invent_an_update(): void
    {
        // "v2.0.0" and "2.0.0" are the same release. Reporting one as newer than
        // the other is the classic way a badge becomes permanent noise.
        $result = $this->check()->refresh(
            ['a/b' => '2.0.0'],
            $this->feed(['a/b' => ['v2.0.0']])
        );

        $this->assertSame([], $result['updates']);
    }

    public function test_a_site_on_a_dev_branch_is_not_offered_a_tagged_release(): void
    {
        /*
         * 🚨 And vice versa. Mixing them produces "update available: dev-main"
         * on a forum deliberately pinned to a stable tag, which trains people to
         * ignore the badge entirely.
         */
        $result = $this->check()->refresh(
            ['a/b' => 'dev-main'],
            $this->feed(['a/b' => ['dev-main', '1.0.0', '2.0.0']])
        );

        $this->assertSame([], $result['updates']);
    }

    public function test_a_site_on_a_stable_tag_is_not_offered_a_dev_branch(): void
    {
        $result = $this->check()->refresh(
            ['a/b' => '1.0.0'],
            $this->feed(['a/b' => ['dev-main', '1.0.0']])
        );

        $this->assertSame([], $result['updates']);
    }

    public function test_a_package_it_cannot_check_is_named_rather_than_assumed_current(): void
    {
        // 🚨 Leaving a private package out would imply it is up to date, which is
        // a guess presented as a fact.
        $result = $this->check()->refresh(
            ['public/one' => '1.0.0', 'private/two' => '1.0.0'],
            $this->feed(['public/one' => ['1.0.0']])
        );

        $this->assertSame(['private/two'], $result['uncheckable']);
    }

    public function test_the_answer_is_cached_and_ages(): void
    {
        $check = new UpdateCheck($this->cache, freshFor: 3600);

        $this->assertTrue($check->isStale(), 'never checked is stale');

        $check->refresh(['a/b' => '1.0.0'], $this->feed(['a/b' => ['2.0.0']]));

        $this->assertFalse($check->isStale());
        $this->assertSame(['from' => '1.0.0', 'to' => '2.0.0'], $check->cached()['updates']['a/b']);
    }

    public function test_only_extensions_and_flarum_itself_are_worth_checking(): void
    {
        /*
         * 🚨 Run against a real forum this returned 59 "updates", nearly all
         * transitive — illuminate/collections 13.30.0 → 13.30.1, guzzle 7 → 8.
         * Nobody updates those individually; they arrive with the extension that
         * needs them, and several are pinned by flarum/core so the newer version
         * cannot be installed at all. A badge showing 59 when 4 things matter is
         * a badge people stop reading.
         */
        $interesting = $this->check()->interesting([
            ['name' => 'ernestdefoe/page-builder', 'version' => '3.5.0', 'type' => 'flarum-extension'],
            ['name' => 'flarum/core', 'version' => '2.0.0-rc.8', 'type' => 'library'],
            ['name' => 'illuminate/collections', 'version' => '13.30.0', 'type' => 'library'],
            ['name' => 'guzzlehttp/guzzle', 'version' => '7.15.5', 'type' => 'library'],
        ]);

        $this->assertSame(
            ['ernestdefoe/page-builder', 'flarum/core'],
            array_keys($interesting)
        );
    }

    public function test_a_library_the_forum_requires_itself_is_checked_and_its_dependencies_are_not(): void
    {
        /*
         * fof/redis is a Composer library, not an extension, so it went
         * unreported and its stable release unseen (Ernest, 2026-10-10). The
         * forum requires it in its own composer.json; predis/predis arrives
         * with it, and nobody chose that.
         */
        $interesting = $this->check()->interesting([
            ['name' => 'fof/redis', 'version' => 'v2.0.0-rc.3', 'type' => 'library'],
            ['name' => 'predis/predis', 'version' => 'v2.4.0', 'type' => 'library'],
            ['name' => 'ernestdefoe/page-builder', 'version' => '3.5.0', 'type' => 'flarum-extension'],
        ], ['fof/redis', 'ernestdefoe/page-builder']);

        $this->assertSame(['fof/redis', 'ernestdefoe/page-builder'], array_keys($interesting));
    }

    public function test_direct_requires_are_packages_not_platform_requirements(): void
    {
        $dir = sys_get_temp_dir().'/mw-req-'.bin2hex(random_bytes(6));
        mkdir($dir);
        file_put_contents($dir.'/composer.json', json_encode(['require' => [
            'php' => '^8.3', 'ext-json' => '*', 'lib-pcre' => '*', 'flarum/core' => '^2.0', 'fof/redis' => '^2.0',
        ]]));

        try {
            $this->assertSame(['flarum/core', 'fof/redis'], UpdateCheck::directRequires($dir.'/composer.json'));
            $this->assertSame([], UpdateCheck::directRequires($dir.'/missing.json'));
        } finally {
            unlink($dir.'/composer.json');
            rmdir($dir);
        }
    }

    public function test_a_never_checked_cache_reads_as_empty_rather_than_erroring(): void
    {
        $cached = $this->check()->cached();

        $this->assertNull($cached['checkedAt']);
        $this->assertSame([], $cached['updates']);
    }

    /**
     * 🚨 A branch install is not a failed check.
     *
     * `dev-main` has no version to be newer than, and whether the branch has
     * moved is something only a resolve can answer. Counting these as
     * "could not be checked" told an admin that four extensions were a problem
     * when they were reachable and fine — two wrong things in one sentence.
     */
    public function test_a_branch_install_is_reported_as_tracking_not_as_uncheckable(): void
    {
        $check = new UpdateCheck($this->cache);

        $result = $check->refresh(
            ['vendor/branchy' => 'dev-main', 'vendor/tagged' => '1.0.0'],
            fn (string $name) => $name === 'vendor/tagged' ? ['1.0.0', '1.1.0'] : null
        );

        $this->assertSame(['vendor/branchy'], $result['tracking']);
        $this->assertSame([], $result['uncheckable']);
        $this->assertSame(['from' => '1.0.0', 'to' => '1.1.0'], $result['updates']['vendor/tagged']);
    }

    /** And it costs no request, which is what keeps a nightly check cheap. */
    public function test_a_branch_install_is_never_fetched(): void
    {
        $check = new UpdateCheck($this->cache);
        $asked = [];

        $check->refresh(
            ['vendor/branchy' => 'dev-main', 'vendor/other' => '1.x-dev'],
            function (string $name) use (&$asked) {
                $asked[] = $name;

                return null;
            }
        );

        $this->assertSame([], $asked, 'Nothing to ask: neither has a version to compare.');
    }

    /** Something genuinely unreachable is still named, not quietly dropped. */
    public function test_an_unreachable_package_is_still_reported(): void
    {
        $check = new UpdateCheck($this->cache);

        $result = $check->refresh(['vendor/private' => '1.0.0'], fn () => null);

        $this->assertSame(['vendor/private'], $result['uncheckable']);
        $this->assertSame([], $result['tracking']);
    }

    /**
     * 🚨 A fresh install has no storage/extension-manager yet — nothing makes it until
     * the first run. The check used to write into the missing directory, the @
     * hid it, and the screen said "Not checked yet" forever.
     */
    public function test_the_result_is_kept_when_its_directory_does_not_exist_yet(): void
    {
        $dir = sys_get_temp_dir().'/mw-fresh-'.bin2hex(random_bytes(6));
        $check = new UpdateCheck($dir.'/extension-manager/updates.json');

        $check->refresh(['a/b' => '1.0.0'], fn () => ['1.0.0', '1.1.0']);

        $this->assertSame(['from' => '1.0.0', 'to' => '1.1.0'], $check->cached()['updates']['a/b'] ?? null);
        $this->assertFalse($check->isStale());

        @unlink($dir.'/extension-manager/updates.json');
        @rmdir($dir.'/extension-manager');
        @rmdir($dir);
    }

    /**
     * 🚨 ClaudiusH, 2026-10-07: straight after updating Mobile Tab to 2.0.1 its
     * card still offered "2.0.0 → 2.0.1", from the check saved before it.
     */
    public function test_an_update_already_installed_is_no_longer_offered(): void
    {
        file_put_contents($this->cache, json_encode(['checkedAt' => time(), 'updates' => [
            'acpl/mobile-tab' => ['from' => '2.0.0', 'to' => '2.0.1'],
            'flarum/extension-manager' => ['from' => 'v1.13.0', 'to' => 'v1.14.0'],
            'fof/upload' => ['from' => '2.0.0-beta.7', 'to' => '2.0.2'],
            'acme/gone' => ['from' => '1.0.0', 'to' => '1.1.0'],
        ]]));
        $lock = $this->cache.'.lock';
        file_put_contents($lock, json_encode(['packages' => [
            ['name' => 'acpl/mobile-tab', 'type' => 'flarum-extension', 'version' => '2.0.1'],
            ['name' => 'flarum/extension-manager', 'type' => 'flarum-extension', 'version' => 'v1.14.0'],
            ['name' => 'fof/upload', 'type' => 'flarum-extension', 'version' => '2.0.0'],
        ]]));

        $updates = $this->check()->current($lock)['updates'];
        @unlink($lock);

        $this->assertSame(['fof/upload', 'acme/gone'], array_keys($updates));
        // Part of the way there: still offered, from where it really is now.
        $this->assertSame(['from' => '2.0.0', 'to' => '2.0.2'], $updates['fof/upload']);
    }

    public function test_p2_is_expanded_so_every_version_gets_its_release_link(): void
    {
        // As Packagist sends it: source on the first entry only, inherited after.
        [$versions, $notes] = UpdateCheck::readP2([
            ['version' => 'v1.2.0', 'source' => ['url' => 'https://github.com/acme/widget.git', 'type' => 'git']],
            ['version' => 'v1.1.0'],
            ['version' => 'dev-main'],
        ]);

        $this->assertSame(['v1.2.0', 'v1.1.0', 'dev-main'], $versions);
        $this->assertSame([
            'v1.2.0' => 'https://github.com/acme/widget/releases/tag/v1.2.0',
            'v1.1.0' => 'https://github.com/acme/widget/releases/tag/v1.1.0',
        ], $notes);
    }

    public function test_an_unset_source_stops_being_inherited(): void
    {
        [, $notes] = UpdateCheck::readP2([
            ['version' => '2.0.0', 'source' => ['url' => 'https://github.com/acme/widget.git']],
            ['version' => '1.0.0', 'source' => '__unset'],
        ]);

        $this->assertSame(['2.0.0'], array_keys($notes));
    }

    public function test_only_github_tags_get_a_release_link(): void
    {
        $this->assertSame('https://github.com/a/b/releases/tag/1.0.0', UpdateCheck::releaseUrl('git@github.com:a/b.git', '1.0.0'));
        $this->assertSame('https://github.com/a/b.c/releases/tag/1.0.0', UpdateCheck::releaseUrl('https://github.com/a/b.c', '1.0.0'));
        $this->assertNull(UpdateCheck::releaseUrl('https://gitlab.com/a/b.git', '1.0.0'));
        $this->assertNull(UpdateCheck::releaseUrl('https://github.com/a/b.git', 'dev-main'));
        $this->assertNull(UpdateCheck::releaseUrl('https://github.com.evil.example/a/b.git', '1.0.0'));
    }

    public function test_an_update_carries_its_release_link_and_keeps_it_after_a_partial_update(): void
    {
        $check = $this->check();
        $notes = new \ReflectionProperty(UpdateCheck::class, 'releaseNotes');
        $notes->setValue($check, ['a/b' => ['3.0.0' => 'https://github.com/a/b/releases/tag/3.0.0']]);

        $result = $check->refresh(['a/b' => '1.0.0'], $this->feed(['a/b' => ['1.0.0', '2.0.0', '3.0.0']]));

        $this->assertSame('https://github.com/a/b/releases/tag/3.0.0', $result['updates']['a/b']['notes']);

        $lock = $this->cache.'.lock';
        file_put_contents($lock, json_encode(['packages' => [['name' => 'a/b', 'version' => '2.0.0', 'type' => 'flarum-extension']]]));
        $current = $check->current($lock);
        @unlink($lock);

        $this->assertSame(['from' => '2.0.0', 'to' => '3.0.0', 'notes' => 'https://github.com/a/b/releases/tag/3.0.0'], $current['updates']['a/b']);
    }

    public function test_a_private_package_gets_its_release_link_from_the_second_source(): void
    {
        $result = $this->check()->refresh(
            ['acme/paid' => '1.0.0'],
            $this->feed(['acme/paid' => ['1.0.0', '1.1.0']]),
            fn (string $name) => $name === 'acme/paid' ? ['1.1.0' => 'https://github.com/acme/paid/releases/tag/1.1.0'] : [],
        );

        $this->assertSame('https://github.com/acme/paid/releases/tag/1.1.0', $result['updates']['acme/paid']['notes']);
    }
}
