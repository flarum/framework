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
use Laminas\Diactoros\Response\EmptyResponse;
use League\Flysystem\FilesystemInterface;
use Psr\Http\Message\ServerRequestInterface;

class DeleteFaviconController extends AbstractDeleteController
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
    protected function delete(ServerRequestInterface $request)
    {
        $this->assertAdmin($request->getAttribute('actor'));

        $path = $this->settings->get('favicon_path');

        $this->settings->set('favicon_path', null);

        if ($this->uploadDir->has($path)) {
            $this->uploadDir->delete($path);
        }

        return new EmptyResponse(204);
    }
}
