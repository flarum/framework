<?php

declare(strict_types=1);

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Admin;

use Flarum\Forum\Branding\IconSet;
use Flarum\Foundation\AbstractImageValidator;
use Flarum\Foundation\ValidationException;
use Psr\Http\Message\UploadedFileInterface;

class AppIconValidator extends AbstractImageValidator
{
    protected function assertDecodable(UploadedFileInterface $file): void
    {
        parent::assertDecodable($file);

        [$width, $height] = getimagesizefromstring((string) $file->getStream());

        if (max($width, $height) < IconSet::APP_ICON_MIN_SIZE) {
            throw new ValidationException([
                $this->filename => $this->translator->trans('core.api.app_icon_too_small_message', ['size' => IconSet::APP_ICON_MIN_SIZE]),
            ]);
        }
    }
}
