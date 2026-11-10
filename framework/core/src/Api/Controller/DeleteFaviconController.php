<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Forum\Branding\StoredFiles;
use Flarum\Forum\Branding\ThemeImages;
use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Psr\Http\Message\ServerRequestInterface;

class DeleteFaviconController extends AbstractDeleteController
{
    protected Filesystem $uploadDir;
    protected StoredFiles $files;
    protected ThemeImages $themeImages;

    public function __construct(
        protected SettingsRepositoryInterface $settings,
        Factory $filesystemFactory,
        ?StoredFiles $files = null,
        ?ThemeImages $themeImages = null
    ) {
        $this->uploadDir = $filesystemFactory->disk('flarum-assets');
        $this->files = $files ?? resolve(StoredFiles::class);
        $this->themeImages = $themeImages ?? resolve(ThemeImages::class);
    }

    protected function delete(ServerRequestInterface $request): void
    {
        RequestUtil::getActor($request)->assertAdmin();

        $this->themeImages->assertNotSet('favicon', 'favicon');

        $path = $this->settings->get('favicon_path');

        $this->settings->set('favicon_path', null);

        if ($path && $this->uploadDir->exists($path)) {
            $this->uploadDir->delete($path);
        }

        $this->files->delete('favicon_ico_path');
    }
}
