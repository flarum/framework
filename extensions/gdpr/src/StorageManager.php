<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr;

use Flarum\Gdpr\Models\Export;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;

class StorageManager
{
    protected Filesystem $filesystem;

    public function __construct(protected Factory $factory)
    {
        $this->filesystem = $this->factory->disk('gdpr-export');
    }

    /**
     * Stores the export on the disk.
     *
     * @param Export $export
     * @param string $filePath
     *
     * @return void
     */
    public function storeExport(Export $export, string $filePath): void
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Failed to open file at $filePath");
        }

        $this->filesystem->writeStream($this->storageFilename($export), $handle);

        fclose($handle);
    }

    /**
     * @param Export $export
     *
     * @return resource|null The path resource or null on failure.
     */
    public function getStoredExport(Export $export)
    {
        return $this->filesystem->readStream($this->storageFilename($export));
    }

    /**
     * Returns the size of the stored export in bytes.
     *
     * @param Export $export
     *
     * @return int
     */
    public function getStoredExportSize(Export $export): int
    {
        return $this->filesystem->size($this->storageFilename($export));
    }

    /**
     * Deletes the stored export from the disk.
     *
     * @param Export $export
     *
     * @return void
     */
    public function deleteStoredExport(Export $export): void
    {
        $this->filesystem->delete($this->storageFilename($export));
    }

    /**
     * Returns the filename for this export file.
     *
     * @param Export $export
     *
     * @return string
     */
    private function storageFilename(Export $export): string
    {
        return "export-{$export->id}.zip";
    }
}
