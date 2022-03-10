<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database;

interface MigrationRepositoryInterface
{
    /**
     * Get the ran migrations for the given extension.
     *
     * @param string $extension
     * @return array
     */
    public function getRan($extension = null);

    /**
     * Log that a migration was run.
     *
     * @param string $file
     * @param string $extension
     * @return void
     */
    public function log($file, $extension = null);

    /**
     * Remove a migration from the log.
     *
     * @param string $file
     * @param string $extension
     * @return void
     */
    public function delete($file, $extension = null);

    /**
     * Determine if the migration repository exists.
     *
     * @return bool
     */
    public function repositoryExists();
}
