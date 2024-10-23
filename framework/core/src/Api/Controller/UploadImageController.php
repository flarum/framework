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
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Intervention\Image\Interfaces\EncodedImageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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
        Factory $filesystemFactory
    ) {
        parent::__construct($api);

        $this->uploadDir = $filesystemFactory->disk('flarum-assets');
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
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

        return parent::handle($request);
    }

    abstract protected function makeImage(UploadedFileInterface $file): EncodedImageInterface;
}
