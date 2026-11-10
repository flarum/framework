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
use Flarum\ExtensionManager\Exception\RefusedException;
use Flarum\ExtensionManager\Tests\unit\EnglishTranslator;
use Flarum\ExtensionManager\Tests\unit\FakeHttp;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

/**
 * discuss.flarum.org d/40012: an extension list featured and installed
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

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->dir));
    }

    /**
     * Packagist answering with these statuses and bodies, url => [status, body];
     * anything not listed is a 404.
     *
     * @param array<string,array{0:int,1:string}> $responses
     * @param list<string> $asked
     */
    private function answering(array $responses, array &$asked = []): Packagist
    {
        return new Packagist(EnglishTranslator::make(), new Cache($this->dir), new Client(['handler' => function (RequestInterface $request) use ($responses, &$asked) {
            $asked[] = $url = (string) $request->getUri();
            [$status, $body] = $responses[$url] ?? [404, ''];

            return Create::promiseFor(new Response($status, [], $body));
        }]));
    }

    private function p2(string $name, array $release = []): string
    {
        return (string) json_encode(['packages' => [$name => [['version' => '1.0.0', 'require' => ['flarum/core' => '^2.0']] + $release]]]);
    }

    private function packagist(array $responses, array &$asked = []): Packagist
    {
        return new Packagist(EnglishTranslator::make(), new Cache($this->dir), FakeHttp::client(function (string $url) use ($responses, &$asked) {
            $asked[] = $url;

            return $responses[$url] ?? null;
        }));
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

    // ── the install guard ───────────────────────────────────────────────────

    /** It used to look at the first five packages only. */
    public function test_the_install_guard_checks_every_package(): void
    {
        $responses = [];

        foreach (range(1, 6) as $i) {
            $responses["https://repo.packagist.org/p2/acme/p$i.json"] = [200, $this->p2("acme/p$i", $i === 6 ? ['abandoned' => 'acme/new'] : [])];
        }

        $this->expectException(RefusedException::class);
        $this->expectExceptionMessage('acme/p6 has been replaced by acme/new');

        $this->answering($responses)->refuseReplaced(array_map(fn ($i) => "acme/p$i", range(1, 6)), new Compatibility('2.0.0'));
    }

    public function test_the_install_guard_lets_ordinary_and_unknown_packages_through(): void
    {
        $this->answering([
            'https://repo.packagist.org/p2/acme/fine.json' => [200, $this->p2('acme/fine')],
        ])->refuseReplaced(['acme/fine', 'acme/protected'], new Compatibility('2.0.0'));

        // A protected package is a 404 on Packagist: unknown, never refused.
        $this->addToAssertionCount(1);
    }

    /**
     * It used to pass silently when Packagist could not be asked. It cannot
     * vouch for the name then, and the install's own resolve would fail
     * against an unreachable Packagist anyway, so it says try again.
     */
    public function test_the_install_guard_refuses_when_packagist_cannot_be_asked(): void
    {
        foreach ([[503, ''], [500, ''], [429, ''], [200, '<html>Bad gateway</html>']] as [$status, $body]) {
            try {
                $this->answering(['https://repo.packagist.org/p2/acme/w.json' => [$status, $body]])
                    ->refuseReplaced(['acme/w'], new Compatibility('2.0.0'));
                $this->fail("$status was let through");
            } catch (RefusedException $e) {
                $this->assertSame('packagist_unreachable', $e->getType());
                $this->assertSame(503, $e->getStatus());
                $this->assertStringContainsString('Packagist could not be reached to check whether acme/w has been replaced', $e->getMessage());
            }
        }

        $this->expectException(RefusedException::class);

        (new Packagist(EnglishTranslator::make(), new Cache($this->dir), FakeHttp::client(fn () => null)))
            ->refuseReplaced(['acme/w'], new Compatibility('2.0.0'));
    }

    /** A replacement is named before an unreachable package, whatever the order. */
    public function test_a_replaced_package_is_reported_before_one_that_could_not_be_asked(): void
    {
        $this->expectExceptionMessage('acme/old has been replaced by acme/new');

        $this->answering([
            'https://repo.packagist.org/p2/acme/down.json' => [503, ''],
            'https://repo.packagist.org/p2/acme/old.json' => [200, $this->p2('acme/old', ['abandoned' => 'acme/new'])],
        ])->refuseReplaced(['acme/down', 'acme/old'], new Compatibility('2.0.0'));
    }

    // ── what is cached ──────────────────────────────────────────────────────

    /**
     * A 200 whose body does not parse (a proxy's error page, a truncated
     * answer) used to be cached as "unknown" for a day.
     */
    public function test_an_answer_that_does_not_parse_is_not_cached(): void
    {
        $asked = [];
        $packagist = $this->answering(['https://repo.packagist.org/p2/acme/w.json' => [200, '<html>Bad gateway</html>']], $asked);

        $packagist->verdicts(['acme/w'], new Compatibility('2.0.0'));
        $packagist->verdicts(['acme/w'], new Compatibility('2.0.0'));
        $this->assertCount(2, $asked);

        $working = $this->answering(['https://repo.packagist.org/p2/acme/w.json' => [200, $this->p2('acme/w')]], $asked);

        $this->assertTrue($working->verdicts(['acme/w'], new Compatibility('2.0.0'))['acme/w']['compatible']);
    }

    /** The same for an untagged package whose branch file could not be read. */
    public function test_an_untagged_package_whose_branches_could_not_be_read_is_not_cached(): void
    {
        $asked = [];
        $packagist = $this->answering([
            'https://repo.packagist.org/p2/acme/w.json' => [200, (string) json_encode(['packages' => ['acme/w' => []]])],
            'https://repo.packagist.org/p2/acme/w~dev.json' => [502, ''],
        ], $asked);

        $verdict = $packagist->verdicts(['acme/w'], new Compatibility('2.0.0'))['acme/w'];
        $packagist->verdicts(['acme/w'], new Compatibility('2.0.0'));

        $this->assertNull($verdict['compatible']);
        $this->assertTrue($verdict['unreachable']);
        $this->assertCount(4, $asked, 'asked again the second time');
    }

    /** An untagged package with no branches either is a real "unknown", and cached. */
    public function test_a_package_with_nothing_published_is_cached_as_unknown(): void
    {
        $asked = [];
        $packagist = $this->answering([
            'https://repo.packagist.org/p2/acme/w.json' => [200, (string) json_encode(['packages' => ['acme/w' => []]])],
        ], $asked);

        $verdict = $packagist->verdicts(['acme/w'], new Compatibility('2.0.0'))['acme/w'];
        $packagist->verdicts(['acme/w'], new Compatibility('2.0.0'));

        $this->assertNull($verdict['compatible']);
        $this->assertArrayNotHasKey('unreachable', $verdict);
        $this->assertCount(2, $asked, 'the main file and the branch file, once');
    }
}
