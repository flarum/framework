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

class UploadLogoController extends ShowForumController
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

        $file = Arr::get($request->getUploadedFiles(), 'logo');

        $manager = new ImageManager;

        $encodedImage = $manager->make($file->getStream())->heighten(60, function ($constraint) {
            $constraint->upsize();
        })->encode('png');

        if (($path = $this->settings->get('logo_path')) && $this->uploadDir->has($path)) {
            $this->uploadDir->delete($path);
        }

        $uploadName = 'logo-'.Str::lower(Str::random(8)).'.png';

        $this->uploadDir->write($uploadName, $encodedImage);

        $this->settings->set('logo_path', $uploadName);

        return parent::data($request, $document);
    }
}
