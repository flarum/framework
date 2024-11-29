<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\JsonApi;
use Flarum\Http\RequestUtil;
use Flarum\Locale\TranslatorInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\EncodedImageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

abstract class UploadImageController extends ShowForumController
{
    protected Filesystem $uploadDir;
    protected string $fileExtension = 'png';
    protected string $filePathSettingKey = '';
    protected string $filenamePrefix = '';

    public function __construct(
        JsonApi $api,
        protected SettingsRepositoryInterface $settings,
        protected ImageManager $imageManager,
        protected TranslatorInterface $translator,
        Factory $filesystemFactory
    ) {
        parent::__construct($api);

        $this->uploadDir = $filesystemFactory->disk('flarum-assets');
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $filenamePrefix = $this->filenamePrefix($request);

        $file = Arr::get($request->getUploadedFiles(), $filenamePrefix);

        $encodedImage = $this->makeImage($file);

        $filePathSettingKey = $this->filePathSettingKey($request, $file);

        if (($path = $this->settings->get($filePathSettingKey)) && $this->uploadDir->exists($path)) {
            $this->uploadDir->delete($path);
        }

        $uploadName = $filenamePrefix.'-'.Str::lower(Str::random(8)).'.'.$this->fileExtension($request, $file);

        $this->uploadDir->put($uploadName, $encodedImage);

        $this->settings->set($filePathSettingKey, $uploadName);

        return parent::handle(
            // The parent controller expects a show forum request.
            // `GET /api/forum`
            $request->withMethod('GET')->withUri($request->getUri()->withPath('/api/forum'))
        );
    }

    abstract protected function makeImage(UploadedFileInterface $file): EncodedImageInterface|StreamInterface;

    protected function fileExtension(ServerRequestInterface $request, UploadedFileInterface $file): string
    {
        return $this->fileExtension;
    }

    protected function filePathSettingKey(ServerRequestInterface $request, UploadedFileInterface $file): string
    {
        return $this->filePathSettingKey;
    }

    protected function filenamePrefix(ServerRequestInterface $request): string
    {
        return $this->filenamePrefix;
    }
}
