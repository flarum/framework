<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Forum\Branding\ThemeImages;
use Flarum\Http\RequestUtil;
use Flarum\Mail\EmailLogo;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Psr\Http\Message\ServerRequestInterface;

class DeleteLogoController extends AbstractDeleteController
{
    protected string $filePathSettingKey = 'logo_path';
    protected string $themeImage = 'logo';
    protected Filesystem $uploadDir;
    protected ThemeImages $themeImages;

    public function __construct(
        protected SettingsRepositoryInterface $settings,
        Factory $filesystemFactory,
        protected EmailLogo $emailLogo,
        ?ThemeImages $themeImages = null
    ) {
        $this->uploadDir = $filesystemFactory->disk('flarum-assets');
        $this->themeImages = $themeImages ?? resolve(ThemeImages::class);
    }

    protected function delete(ServerRequestInterface $request): void
    {
        RequestUtil::getActor($request)->assertAdmin();

        $this->themeImages->assertNotSet($this->themeImage, str_replace('_', '-', $this->themeImage));

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
