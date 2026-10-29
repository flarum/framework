<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Admin\FaviconValidator;
use Flarum\Forum\Branding\IconSet;
use Flarum\Forum\Branding\StoredFiles;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

class UploadFaviconController extends UploadImageController
{
    protected string $filePathSettingKey = 'favicon_path';
    protected string $filenamePrefix = 'favicon';
    protected ?string $validator = FaviconValidator::class;
    protected ?string $themeImage = 'favicon';

    protected ?string $ico = null;

    protected function makeImage(UploadedFileInterface $file): string
    {
        if ($this->container->make(FaviconValidator::class)->isIco($file)) {
            $this->fileExtension = 'ico';
            $this->ico = null;

            return (string) $file->getStream();
        }

        $favicon = $this->container->make(IconSet::class)->favicon((string) $file->getStream());

        $this->fileExtension = 'png';
        $this->ico = $favicon['ico'];

        return $favicon['png'];
    }

    protected function afterStore(ServerRequestInterface $request, UploadedFileInterface $file): void
    {
        $this->container->make(StoredFiles::class)->replace('favicon_ico_path', $this->ico, 'favicon', 'ico');
    }
}
