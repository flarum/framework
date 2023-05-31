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
     */
    public function getRan(?string $extension = null): array;

    /**
     * Log that a migration was run.
     */
    public function log(string $file, ?string $extension = null): void;

    /**
     * Remove a migration from the log.
     */
    public function delete(string $file, ?string $extension = null): void;

    /**
     * Determine if the migration repository exists.
     */
    public function repositoryExists(): bool;
}
