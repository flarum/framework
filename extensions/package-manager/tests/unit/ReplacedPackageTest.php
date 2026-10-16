<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Discover\Cache;
use Flarum\ExtensionManager\Discover\Compatibility;
use Flarum\ExtensionManager\Discover\Packagist;
use PHPUnit\Framework\TestCase;

/**
 * 🚨 discuss.flarum.org d/40012: the previous Extension Manager featured and installed
 * v17development/flarum-seo, an old name whose repository moved to fof/seo and
 * now declares it REPLACES the old name. The result was FoF SEO under the wrong
 * id, refusing to enable. The shapes below are Packagist's real ones, minified.
 */
class ReplacedPackageTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-replaced-'.bin2hex(random_bytes(4));
    }

    private function packagist(array $responses, array &$asked = []): Packagist
    {
        return new Packagist(new Cache($this->dir), function (string $url) use ($responses, &$asked) {
            $asked[] = $url;

            return $responses[$url] ?? null;
        });
    }

    public function test_minified_metadata_is_expanded_so_an_inherited_requirement_counts(): void
    {
        $v = (new Compatibility('2.0.0'))->verdict('acme/w', ['minified' => 'composer/2.0', 'packages' => ['acme/w' => [
            ['version' => 'v2.1.0-beta.1', 'require' => ['flarum/core' => '^2.0']],
            ['version' => 'v2.0.0'],
        ]]]);

        // The stable release inherits ^2.0 from the entry above it, so it wins.
        $this->assertSame('v2.0.0', $v['version']);
        $this->assertSame('stable', $v['stability']);
    }

    public function test_unset_markers_remove_a_key_when_expanding(): void
    {
        $out = Compatibility::expand([['a' => 1, 'b' => 2], ['b' => '__unset']], 'composer/2.0');

        $this->assertSame([['a' => 1, 'b' => 2], ['a' => 1]], $out);
    }

    public function test_a_package_that_replaces_its_own_name_is_resolved_to_the_new_name(): void
    {
        $p2 = ['minified' => 'composer/2.0', 'packages' => ['v17development/flarum-seo' => [
            ['version' => '4.0.0-beta.10', 'require' => ['flarum/core' => '^2.0'],
                'replace' => ['v17development/flarum-seo' => '*'],
                'source' => ['type' => 'git', 'url' => 'https://github.com/FriendsOfFlarum/seo.git', 'reference' => 'abc123']],
        ]]];

        $asked = [];
        $verdicts = $this->packagist([
            'https://repo.packagist.org/p2/v17development/flarum-seo.json' => json_encode($p2),
            'https://raw.githubusercontent.com/FriendsOfFlarum/seo/abc123/composer.json' => json_encode(['name' => 'fof/seo']),
        ], $asked)->verdicts(['v17development/flarum-seo'], new Compatibility('2.0.0'));

        $v = $verdicts['v17development/flarum-seo'];
        $this->assertTrue($v['replaced']);
        $this->assertSame('fof/seo', $v['replacedBy']);
        $this->assertArrayNotHasKey('source', $v);
    }

    public function test_an_abandoned_package_names_its_replacement_without_reading_the_repository(): void
    {
        $asked = [];
        $p2 = ['minified' => 'composer/2.0', 'packages' => ['jaspervriends/flarum-seo' => [
            ['version' => '1.0.0', 'require' => ['flarum/core' => '^1.0'], 'abandoned' => 'v17development/flarum-seo'],
        ]]];

        $v = $this->packagist(['https://repo.packagist.org/p2/jaspervriends/flarum-seo.json' => json_encode($p2)], $asked)
            ->verdicts(['jaspervriends/flarum-seo'], new Compatibility('2.0.0'))['jaspervriends/flarum-seo'];

        $this->assertTrue($v['replaced']);
        $this->assertSame('v17development/flarum-seo', $v['replacedBy']);
        // Its own metadata, then the replacement's (is that replaced too?); never GitHub.
        $this->assertSame([], preg_grep('#githubusercontent#', $asked));
    }

    public function test_an_ordinary_package_is_not_replaced_and_costs_one_request(): void
    {
        $asked = [];
        $p2 = ['packages' => ['fof/seo' => [
            ['version' => '4.0.0', 'require' => ['flarum/core' => '^2.0'], 'replace' => ['v17development/flarum-seo' => '*']],
        ]]];

        $v = $this->packagist(['https://repo.packagist.org/p2/fof/seo.json' => json_encode($p2)], $asked)
            ->verdicts(['fof/seo'], new Compatibility('2.0.0'))['fof/seo'];

        $this->assertFalse($v['replaced']);
        $this->assertCount(1, $asked);
    }

    public function test_a_source_url_off_github_is_never_followed(): void
    {
        $asked = [];
        $p2 = ['packages' => ['acme/old' => [
            ['version' => '1.0.0', 'require' => ['flarum/core' => '^2.0'], 'replace' => ['acme/old' => '*'],
                'source' => ['url' => 'http://169.254.169.254/latest/meta-data', 'reference' => 'x']],
        ]]];

        $v = $this->packagist(['https://repo.packagist.org/p2/acme/old.json' => json_encode($p2)], $asked)
            ->verdicts(['acme/old'], new Compatibility('2.0.0'))['acme/old'];

        $this->assertTrue($v['replaced']);
        $this->assertNull($v['replacedBy']);
        $this->assertCount(1, $asked);
    }

    public function test_a_chain_of_replacements_is_followed_to_the_end(): void
    {
        $p = fn (string $n, array $extra) => json_encode(['packages' => [$n => [['version' => '1.0.0', 'require' => ['flarum/core' => '^2.0']] + $extra]]]);

        $v = $this->packagist([
            'https://repo.packagist.org/p2/a/one.json' => $p('a/one', ['abandoned' => 'a/two']),
            'https://repo.packagist.org/p2/a/two.json' => $p('a/two', ['abandoned' => 'a/three']),
            'https://repo.packagist.org/p2/a/three.json' => $p('a/three', []),
        ])->verdicts(['a/one'], new Compatibility('2.0.0'))['a/one'];

        $this->assertSame('a/three', $v['replacedBy']);
    }

    public function test_a_circle_of_replacements_stops(): void
    {
        $p = fn (string $n, string $to) => json_encode(['packages' => [$n => [['version' => '1.0.0', 'require' => ['flarum/core' => '^2.0'], 'abandoned' => $to]]]]);
        $asked = [];

        $v = $this->packagist([
            'https://repo.packagist.org/p2/a/one.json' => $p('a/one', 'a/two'),
            'https://repo.packagist.org/p2/a/two.json' => $p('a/two', 'a/one'),
        ], $asked)->verdicts(['a/one'], new Compatibility('2.0.0'))['a/one'];

        $this->assertTrue($v['replaced']);
        $this->assertLessThanOrEqual(3, count($asked));
    }
}
