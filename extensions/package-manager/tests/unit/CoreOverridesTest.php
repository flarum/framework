<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Plan\CoreOverrides;
use PHPUnit\Framework\TestCase;

/**
 * An extension that ships its own copy of one of Flarum's classes passes
 * every `require` check and still breaks on the first Flarum release that
 * changes the class. irmmr/flarum-ext-rtl did exactly this with LessCompiler,
 * and Flarum 2.0.2 answered 500 to every page (ClaudiusH, 2026-10-18).
 */
class CoreOverridesTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/mw-overrides-'.bin2hex(random_bytes(4));
        $this->put('flarum/core/src/Frontend/Compiler/LessCompiler.php', "<?php\nnamespace Flarum\\Frontend\\Compiler;\nclass LessCompiler {}\n");
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->dir));
    }

    private function put(string $path, string $body): void
    {
        @mkdir(dirname($this->dir.'/'.$path), 0775, true);
        file_put_contents($this->dir.'/'.$path, $body);
    }

    private function lock(array ...$extra): array
    {
        return array_merge([['name' => 'flarum/core', 'autoload' => ['psr-4' => ['Flarum\\' => 'src/']]]], $extra);
    }

    private function find(array $lock): array
    {
        return (new CoreOverrides())->find($lock, $this->dir);
    }

    public function test_a_copy_of_a_flarum_class_loaded_through_files_is_found(): void
    {
        $this->put('irmmr/flarum-ext-rtl/src/Overrides/Frontend/Compiler/LessCompiler.php', "<?php\n\n/* Flarum's, patched */\n\nnamespace Flarum\\Frontend\\Compiler;\n\nuse Less_Parser;\n\n/** @internal */\nclass LessCompiler extends RevisionCompiler\n{\n}\n");

        $found = $this->find($this->lock([
            'name' => 'irmmr/flarum-ext-rtl',
            'autoload' => ['files' => ['src/Overrides/Frontend/Compiler/LessCompiler.php'], 'psr-4' => ['Irmmr\\FlarumRtlSupport\\' => 'src/']],
        ]));

        $this->assertSame([['package' => 'irmmr/flarum-ext-rtl', 'classes' => ['Flarum\\Frontend\\Compiler\\LessCompiler']]], $found);
    }

    public function test_a_namespace_under_flarums_name_that_replaces_nothing_is_not_flagged(): void
    {
        // Flarum has no Classifieds folder, so this is the extension's own namespace.
        $this->put('ernestdefoe/classifieds/src/Listing.php', "<?php\nnamespace Flarum\\Classifieds;\nclass Listing {}\n");

        $this->assertSame([], $this->find($this->lock([
            'name' => 'ernestdefoe/classifieds',
            'autoload' => ['psr-4' => ['Flarum\\Classifieds\\' => 'src/']],
        ])));
    }

    public function test_a_namespace_mapped_over_one_flarum_fills_is_flagged(): void
    {
        $this->assertSame(
            [['package' => 'acme/compiler', 'classes' => ['Flarum\\Frontend\\Compiler\\*']]],
            $this->find($this->lock(['name' => 'acme/compiler', 'autoload' => ['psr-4' => ['Flarum\\Frontend\\Compiler\\' => 'src/']]]))
        );
    }

    public function test_flarums_own_packages_and_ordinary_extensions_are_left_alone(): void
    {
        $this->put('acme/helpers/helpers.php', "<?php\nnamespace Acme\\Helpers;\nfunction thing() {}\n");

        $this->assertSame([], $this->find($this->lock(
            ['name' => 'flarum/tags', 'autoload' => ['psr-4' => ['Flarum\\Tags\\' => 'src/']]],
            ['name' => 'acme/helpers', 'autoload' => ['files' => ['helpers.php']]],
        )));
    }

    public function test_a_failure_naming_the_replaced_class_names_the_package(): void
    {
        $overrides = [['package' => 'irmmr/flarum-ext-rtl', 'classes' => ['Flarum\\Frontend\\Compiler\\LessCompiler']]];

        $this->assertSame(
            ['package' => 'irmmr/flarum-ext-rtl', 'class' => 'Flarum\\Frontend\\Compiler\\LessCompiler'],
            CoreOverrides::culpritFor('Error: Call to undefined method Flarum\\Frontend\\Compiler\\LessCompiler::fontRevisionIn()', $overrides)
        );
        $this->assertNull(CoreOverrides::culpritFor('Error: Call to undefined method Flarum\\Http\\Server::run()', $overrides));
    }

    public function test_a_path_climbing_out_of_the_package_is_never_read(): void
    {
        $this->assertSame([], $this->find($this->lock(['name' => 'acme/sneaky', 'autoload' => ['files' => ['../../flarum/core/src/Frontend/Compiler/LessCompiler.php']]])));
    }
}
