<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Data;

use Illuminate\Support\Str;

class Assets extends Type
{
    public static function dataType(): string
    {
        return 'Avatar';
    }

    public function export(): ?array
    {
        $dataExport = [];

        if ($this->user->avatar_url) {
            $fileName = $this->getAvatarFileName();
            $fileType = Str::afterLast($fileName, '.');

            $filesystem = $this->getDisk('flarum-avatars');

            if ($filesystem->exists($fileName)) {
                $file = $filesystem->get($fileName);

                $dataExport[] = ["avatars/avatar-{$this->user->id}.{$fileType}" => $file];
            }
        }

        return $dataExport;
    }

    public static function anonymizeDescription(): string
    {
        return self::deleteDescription();
    }

    public function anonymize(): void
    {
        // Anonymization isn't really possible with avatars, just delete 'em.
        $this->delete();
    }

    public function delete(): void
    {
        if ($this->user->avatar_url) {
            $filesystem = $this->getDisk('flarum-avatars');
            $fileName = $this->getAvatarFileName();

            $filesystem->exists($fileName) && $filesystem->delete($fileName);
        }
    }

    private function getAvatarFileName(): string
    {
        return Str::afterLast($this->user->avatar_url, '/');
    }
}
