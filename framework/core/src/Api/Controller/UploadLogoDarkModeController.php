<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Admin\LogoValidator;

class UploadLogoDarkModeController extends UploadLogoController
{
    protected string $filePathSettingKey = 'logo_dark_mode_path';
    protected string $filenamePrefix = 'logo-dark-mode';
    protected ?string $validator = LogoValidator::class;
}
