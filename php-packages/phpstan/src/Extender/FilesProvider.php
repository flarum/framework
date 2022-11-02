<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PHPStan\Extender;

class FilesProvider
{
    /** @var string[] */
    private $cachedExtenderFiles;
    /** @var string[] */
    private $paths;

    public function __construct(array $paths)
    {
        $this->paths = $paths;
    }

    public function getExtenderFiles(): array
    {
        if ($this->cachedExtenderFiles === null) {
            $this->cachedExtenderFiles = $this->findExtenderFiles();
        }

        return $this->cachedExtenderFiles;
    }

    private function findExtenderFiles(): array
    {
        $extenderFiles = [];

        foreach ($this->paths as $path) {
            $extenderFile = str_replace('src', 'extend.php', $path);

            if (file_exists($extenderFile)) {
                $extenderFiles[] = $extenderFile;
            }
        }

        return $extenderFiles;
    }
}
