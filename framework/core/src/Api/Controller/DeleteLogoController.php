<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Http\RequestUtil;
use Flarum\Mail\EmailLogo;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Psr\Http\Message\ServerRequestInterface;

class DeleteLogoController extends AbstractDeleteController
{
    protected string $filePathSettingKey = 'logo_path';
    protected Filesystem $uploadDir;

    public function __construct(
        protected SettingsRepositoryInterface $settings,
        Factory $filesystemFactory,
        protected EmailLogo $emailLogo
    ) {
        $this->uploadDir = $filesystemFactory->disk('flarum-assets');
    }

    protected function delete(ServerRequestInterface $request): void
    {
        RequestUtil::getActor($request)->assertAdmin();

        $path = $this->settings->get($this->filePathSettingKey);

        $this->settings->set($this->filePathSettingKey, null);

        if ($path && $this->uploadDir->exists($path)) {
            $this->uploadDir->delete($path);
        }

        $this->afterDelete();
    }

    /**
     * Runs after the logo has been deleted. Removes the email-safe copy that
     * was stored alongside the forum logo.
     */
    protected function afterDelete(): void
    {
        $this->emailLogo->deleteCopy();
    }
}
