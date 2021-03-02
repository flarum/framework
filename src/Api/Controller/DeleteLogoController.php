<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Settings\SettingsRepositoryInterface;
use Laminas\Diactoros\Response\EmptyResponse;
use League\Flysystem\FilesystemInterface;
use Psr\Http\Message\ServerRequestInterface;

class DeleteLogoController extends AbstractDeleteController
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
        $request->getAttribute('actor')->assertAdmin();

        $path = $this->settings->get('logo_path');

        $this->settings->set('logo_path', null);

        if ($this->uploadDir->has($path)) {
            $this->uploadDir->delete($path);
        }

        return new EmptyResponse(204);
    }
}
