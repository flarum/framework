<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Mail\EmailLogo;

class DeleteEmailLogoController extends DeleteLogoController
{
    protected string $filePathSettingKey = EmailLogo::PATH_KEY;

    // Only the uploaded email logo goes; the copy of the forum logo stays, as
    // it's what emails fall back to.
    protected function afterDelete(): void
    {
        $this->settings->set(EmailLogo::SIZE_KEY, null);
    }
}
