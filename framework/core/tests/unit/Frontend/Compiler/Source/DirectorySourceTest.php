<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Frontend\Compiler\Source;

use Flarum\Frontend\Compiler\Source\DirectorySource;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DirectorySourceTest extends TestCase
{
    #[Test]
    public function it_does_not_create_a_missing_directory()
    {
        $path = sys_get_temp_dir().'/flarum-directory-source-'.uniqid();

        $source = new DirectorySource($path, 'acme-chunks');

        $this->assertSame([], $source->getFilesystem()->allFiles());
        $this->assertDirectoryDoesNotExist($path);
    }

    #[Test]
    public function it_lists_files_in_an_existing_directory()
    {
        $path = sys_get_temp_dir().'/flarum-directory-source-'.uniqid();
        mkdir($path.'/components', 0777, true);
        file_put_contents($path.'/components/Chunk.js', '');

        try {
            $source = new DirectorySource($path, 'acme-chunks');

            $this->assertSame(['components/Chunk.js'], $source->getFilesystem()->allFiles());
        } finally {
            unlink($path.'/components/Chunk.js');
            rmdir($path.'/components');
            rmdir($path);
        }
    }
}
