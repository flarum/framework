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
use Flarum\ExtensionManager\Tests\unit\EnglishTranslator;
use Flarum\ExtensionManager\Tests\unit\FakeHttp;
use PHPUnit\Framework\TestCase;

/**
 * fof/translate and fof/extension-releases have never
 * been tagged, so Packagist's release file lists nothing and every search said
 * "could not check". Their branches are in the ~dev file. Real shapes below.
 */
class UntaggedPackageTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-untagged-'.bin2hex(random_bytes(4));
    }

    private function verdicts(array $responses, array &$asked = []): array
    {
        $packagist = new Packagist(EnglishTranslator::make(), new Cache($this->dir), FakeHttp::client(function (string $url) use ($responses, &$asked) {
            $asked[] = $url;

            return $responses[$url] ?? null;
        }));

        return $packagist->verdicts(['fof/translate'], new Compatibility('2.0.2'));
    }

    public function test_a_package_with_no_releases_is_judged_on_its_branches(): void
    {
        $v = $this->verdicts([
            'https://repo.packagist.org/p2/fof/translate.json' => '{"minified":"composer/2.0","packages":{"fof/translate":[]},"security-advisories":[]}',
            'https://repo.packagist.org/p2/fof/translate~dev.json' => json_encode(['minified' => 'composer/2.0', 'packages' => ['fof/translate' => [
                ['version' => '2.x-dev', 'version_normalized' => '2.9999999.9999999.9999999-dev', 'require' => ['flarum/core' => '^2.0']],
            ]]]),
        ])['fof/translate'];

        $this->assertTrue($v['compatible']);
        $this->assertSame('2.x-dev', $v['version']);
        $this->assertSame('dev', $v['stability']);
    }

    public function test_a_tagged_package_never_asks_for_its_branches(): void
    {
        $asked = [];
        $v = $this->verdicts([
            'https://repo.packagist.org/p2/fof/translate.json' => json_encode(['minified' => 'composer/2.0', 'packages' => ['fof/translate' => [
                ['version' => '2.0.0', 'require' => ['flarum/core' => '^2.0']],
            ]]]),
        ], $asked)['fof/translate'];

        $this->assertSame('2.0.0', $v['version']);
        $this->assertSame(['https://repo.packagist.org/p2/fof/translate.json'], $asked);
    }

    public function test_branches_that_cannot_be_reached_still_say_could_not_check(): void
    {
        $v = $this->verdicts([
            'https://repo.packagist.org/p2/fof/translate.json' => '{"minified":"composer/2.0","packages":{"fof/translate":[]}}',
        ])['fof/translate'];

        $this->assertNull($v['compatible']);
    }
}
