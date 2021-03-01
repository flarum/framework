<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Intervention\Image\Image;
use League\Flysystem\FilesystemInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Tobscure\JsonApi\Document;

abstract class UploadImageController extends ShowForumController
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var FilesystemInterface
     */
    protected $uploadDir;

    /**
     * @var string
     */
    protected $fileExtension = 'png';

    /**
     * @var string
     */
    protected $filePathSettingKey = '';

    /**
     * @var string
     */
    protected $filenamePrefix = '';

    /**
     * @param SettingsRepositoryInterface $settings
     * @param FilesystemInterface $uploadDir
     */
    public function __construct(SettingsRepositoryInterface $settings, FilesystemInterface $uploadDir)
    {
        $this->settings = $settings;
        $this->uploadDir = $uploadDir;
    }

    /**
     * {@inheritdoc}
     */
    public function data(ServerRequestInterface $request, Document $document)
    {
        $request->getAttribute('actor')->assertAdmin();

        $file = Arr::get($request->getUploadedFiles(), $this->filenamePrefix);

        $encodedImage = $this->makeImage($file);

        if (($path = $this->settings->get($this->filePathSettingKey)) && $this->uploadDir->has($path)) {
            $this->uploadDir->delete($path);
        }

        $uploadName = $this->filenamePrefix.'-'.Str::lower(Str::random(8)).'.'.$this->fileExtension;

        $this->uploadDir->write($uploadName, $encodedImage);

        $this->settings->set($this->filePathSettingKey, $uploadName);

        return parent::data($request, $document);
    }

    /**
     * @param UploadedFileInterface $file
     * @return Image
     */
    abstract protected function makeImage(UploadedFileInterface $file): Image;
}
