<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr;

use Flarum\Foundation\Paths;
use Flarum\Gdpr\Contracts\DataType;
use Flarum\Gdpr\Models\Export;
use Flarum\Http\UrlGenerator;
use Flarum\Notification\Notification;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Support\Str;
use Symfony\Contracts\Translation\TranslatorInterface;

class Exporter
{
    protected const TEMPORARY_FILE_PREFIX = 'data-export-';

    protected string $storagePath;

    public function __construct(
        Paths $paths,
        protected SettingsRepositoryInterface $settings,
        protected DataProcessor $processor,
        protected Factory $factory,
        protected UrlGenerator $url,
        protected TranslatorInterface $translator,
        protected StorageManager $storageManager,
        protected ZipManager $zipManager
    ) {
        $this->storagePath = $paths->storage;
    }

    public function export(User $user, User $actor): Export
    {
        $tmpDir = $this->getTempDir();

        // tempnam() creates an empty file to claim the name; the archive is
        // written beside it under a longer, unguessable name. Both go when the
        // export ends, however it ends. An export killed outright, by a queue
        // timeout or an out-of-memory worker, is swept up by
        // removeStaleTemporaryFiles() instead.
        $placeholder = tempnam($tmpDir, self::TEMPORARY_FILE_PREFIX.$user->username);

        $file = $placeholder.'-'.Str::random(20);

        try {
            return $this->writeExport($user, $actor, $file);
        } finally {
            foreach ([$placeholder, $file] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }
    }

    protected function writeExport(User $user, User $actor, string $file): Export
    {
        foreach ($this->processor->removableUserColumns() as $column) {
            if ($user->{$column} !== null) {
                $user->{$column} = null;
            }
        }

        foreach ($this->processor->types() as $type => $extension) {
            /** @var DataType $segment */
            $segment = new $type($user, null, $this->factory, $this->settings, $this->url, $this->translator);

            $data = $segment->export();

            if ($data === null) {
                continue;
            }

            // Check if the array is an indexed array of associative arrays
            if (is_array($data) && array_values($data) === $data) {
                // Handling list of associative arrays
                foreach ($data as $subArray) {
                    foreach ($subArray as $filename => $content) {
                        $this->zipManager->addData($filename, $content);
                    }
                }
            } else {
                // Handling single associative array
                foreach ($data as $filename => $content) {
                    $this->zipManager->addData($filename, $content);
                }
            }
        }

        $this->zipManager->save($file);

        $export = Export::exported($user, basename($file), $actor);

        $this->storageManager->storeExport($export, $file);

        return $export;
    }

    /**
     * Remove what exports left in the temporary directory and never got to
     * clean up, because the process running them was killed. Anything older
     * than a day is far older than any export can run.
     */
    public function removeStaleTemporaryFiles(int $olderThanSeconds = 86400): void
    {
        $cutoff = time() - $olderThanSeconds;

        foreach (glob($this->getTempDir().DIRECTORY_SEPARATOR.self::TEMPORARY_FILE_PREFIX.'*') ?: [] as $path) {
            if (is_file($path) && filemtime($path) < $cutoff) {
                unlink($path);
            }
        }
    }

    public function expire(Export $export): void
    {
        $this->storageManager->deleteStoredExport($export);

        Notification::query()
            ->where('type', 'gdprExportAvailable')
            ->where('subject_id', $export->id)
            ->update(['is_deleted' => true]);

        $export->file = null;
        $export->save();
    }

    private function getTempDir(): string
    {
        $tmpDir = $this->storagePath.DIRECTORY_SEPARATOR.'tmp';
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        return $tmpDir;
    }
}
