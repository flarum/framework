<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Console\Command;

class CacheClearCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('cache:clear')
            ->setDescription('Remove all temporary and generated files');
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $this->info('Clearing the cache...');

        $this->removeFilesMatching('assets', '*.js');
        $this->removeFilesMatching('assets', '*.css');
    }

    protected function removeFilesMatching($path, $pattern)
    {
        $this->info("Removing $pattern files in $path...");

        $path = $this->getPath($path);
        array_map('unlink', glob("$path/$pattern"));
    }

    protected function getPath($path)
    {
        return base_path($path);
    }
}
