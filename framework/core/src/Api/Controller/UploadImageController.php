<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Intervention\Image\Image;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Tobscure\JsonApi\Document;

abstract class UploadImageController extends ShowForumController
{
    protected Filesystem $uploadDir;
    protected string $fileExtension = 'png';
    protected string $filePathSettingKey = '';
    protected string $filenamePrefix = '';

    public function __construct(
        protected SettingsRepositoryInterface $settings,
        Factory $filesystemFactory
    ) {
        $this->uploadDir = $filesystemFactory->disk('flarum-assets');
    }

    public function data(ServerRequestInterface $request, Document $document): array
    {
        RequestUtil::getActor($request)->assertAdmin();

        $file = Arr::get($request->getUploadedFiles(), $this->filenamePrefix);

        $encodedImage = $this->makeImage($file);

        if (($path = $this->settings->get($this->filePathSettingKey)) && $this->uploadDir->exists($path)) {
            $this->uploadDir->delete($path);
        }

        $uploadName = $this->filenamePrefix.'-'.Str::lower(Str::random(8)).'.'.$this->fileExtension;

        $this->uploadDir->put($uploadName, $encodedImage);

        $this->settings->set($this->filePathSettingKey, $uploadName);

        return parent::data($request, $document);
    }

    abstract protected function makeImage(UploadedFileInterface $file): Image;
}
