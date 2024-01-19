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
use Psr\Http\Message\ServerRequestInterface;

class DeleteLogoController extends AbstractDeleteController
{
    protected Filesystem $uploadDir;

    public function __construct(
        protected SettingsRepositoryInterface $settings,
        Factory $filesystemFactory
    ) {
        $this->uploadDir = $filesystemFactory->disk('flarum-assets');
    }

    protected function delete(ServerRequestInterface $request): void
    {
        RequestUtil::getActor($request)->assertAdmin();

        $path = $this->settings->get('logo_path');

        $this->settings->set('logo_path', null);

        if ($this->uploadDir->exists($path)) {
            $this->uploadDir->delete($path);
        }
    }
}
