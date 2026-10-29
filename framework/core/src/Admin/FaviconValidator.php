<?php

declare(strict_types=1);

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Admin;

use Flarum\Foundation\AbstractImageValidator;
use Psr\Http\Message\UploadedFileInterface;

class FaviconValidator extends AbstractImageValidator
{
    protected function getAllowedTypes(): array
    {
        return [...parent::getAllowedTypes(), 'ico'];
    }

    protected function assertDecodable(UploadedFileInterface $file): void
    {
        if (! $this->isIco($file)) {
            parent::assertDecodable($file);
        }
    }

    public function isIco(UploadedFileInterface $file): bool
    {
        $info = @getimagesizefromstring((string) $file->getStream());

        return $info !== false && $info[2] === IMAGETYPE_ICO;
    }
}
