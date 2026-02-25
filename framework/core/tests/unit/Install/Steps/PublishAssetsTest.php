<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Install\Steps;

use Flarum\Install\Steps\PublishAssets;
use Flarum\Testing\unit\TestCase;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\Test;

class PublishAssetsTest extends TestCase
{
    private string $vendorPath;
    private string $assetPath;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem;

        // Build a minimal fake vendor tree mirroring fortawesome/font-awesome
        $this->vendorPath = sys_get_temp_dir().'/flarum_publish_assets_test_vendor_'.uniqid();
        $this->assetPath = sys_get_temp_dir().'/flarum_publish_assets_test_assets_'.uniqid();

        $webfontsDir = $this->vendorPath.'/fortawesome/font-awesome/webfonts';
        $this->filesystem->makeDirectory($webfontsDir, 0755, true);

        // FA7 only ships .woff2
        foreach (['fa-solid-900.woff2', 'fa-regular-400.woff2', 'fa-brands-400.woff2', 'fa-v4compatibility.woff2'] as $file) {
            file_put_contents("$webfontsDir/$file", "fake-font-data-$file");
        }
    }

    protected function tearDown(): void
    {
        $this->filesystem->deleteDirectory($this->vendorPath);
        $this->filesystem->deleteDirectory($this->assetPath);

        parent::tearDown();
    }

    #[Test]
    public function it_reads_webfonts_from_fortawesome_font_awesome()
    {
        $step = new PublishAssets($this->vendorPath, $this->assetPath);
        $step->run();

        $this->assertDirectoryExists($this->assetPath.'/fonts');
        $this->assertFileExists($this->assetPath.'/fonts/fa-solid-900.woff2');
        $this->assertFileExists($this->assetPath.'/fonts/fa-regular-400.woff2');
        $this->assertFileExists($this->assetPath.'/fonts/fa-brands-400.woff2');
    }

    #[Test]
    public function it_copies_font_file_contents_correctly()
    {
        $step = new PublishAssets($this->vendorPath, $this->assetPath);
        $step->run();

        $this->assertStringEqualsFile(
            $this->assetPath.'/fonts/fa-solid-900.woff2',
            'fake-font-data-fa-solid-900.woff2'
        );
    }

    #[Test]
    public function it_reverts_by_deleting_fonts_directory()
    {
        $step = new PublishAssets($this->vendorPath, $this->assetPath);
        $step->run();

        $this->assertDirectoryExists($this->assetPath.'/fonts');

        $step->revert();

        $this->assertDirectoryDoesNotExist($this->assetPath.'/fonts');
    }

    #[Test]
    public function it_publishes_only_woff2_files_from_fa7()
    {
        $step = new PublishAssets($this->vendorPath, $this->assetPath);
        $step->run();

        $published = $this->filesystem->allFiles($this->assetPath.'/fonts');
        $extensions = array_unique(array_map(
            fn ($f) => pathinfo($f, PATHINFO_EXTENSION),
            $published
        ));

        // FA7 only ships woff2 — no ttf, woff, eot, svg
        $this->assertEquals(['woff2'], $extensions);
    }
}
