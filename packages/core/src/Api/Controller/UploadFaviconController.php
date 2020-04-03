<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\AssertPermissionTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use League\Flysystem\FilesystemInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class UploadFaviconController extends ShowForumController
{
    use AssertPermissionTrait;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var FilesystemInterface
     */
    protected $uploadDir;

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
        $this->assertAdmin($request->getAttribute('actor'));

        $file = Arr::get($request->getUploadedFiles(), 'favicon');
        $extension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);

        if ($extension === 'ico') {
            $image = $file->getStream();
        } else {
            $manager = new ImageManager;

            $image = $manager->make($file->getStream())->resize(64, 64, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->encode('png');

            $extension = 'png';
        }

        if (($path = $this->settings->get('favicon_path')) && $this->uploadDir->has($path)) {
            $this->uploadDir->delete($path);
        }

        $uploadName = 'favicon-'.Str::lower(Str::random(8)).'.'.$extension;

        $this->uploadDir->write($uploadName, $image);

        $this->settings->set('favicon_path', $uploadName);

        return parent::data($request, $document);
    }
}
