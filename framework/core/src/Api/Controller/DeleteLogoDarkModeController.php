<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

class DeleteLogoDarkModeController extends DeleteLogoController
{
    protected string $filePathSettingKey = 'logo_dark_mode_path';
}
