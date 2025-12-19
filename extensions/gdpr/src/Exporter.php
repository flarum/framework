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

        $file = tempnam($tmpDir, 'data-export-'.$user->username);

        $file .= '-'.Str::random(20);

        foreach ($this->processor->removableUserColumns() as $column) {
            if ($user->{$column} !== null) {
                $user->{$column} = null;
            }
        }

        foreach ($this->processor->types() as $type => $extension) {
            /** @var DataType $segment */
            $segment = new $type($user, null, $this->factory, $this->settings, $this->url, $this->translator);

            $data = $segment->export();

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

        unlink($file);

        return $export;
    }

    public function destroy(Export $export): void
    {
        $this->storageManager->deleteStoredExport($export);

        Notification::query()
            ->where('type', 'gdprExportAvailable')
            ->where('subject_id', $export->id)
            ->update(['is_deleted' => true]);

        $export->delete();
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
