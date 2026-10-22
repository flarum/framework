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
}
