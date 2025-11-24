<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr;

use Flarum\Foundation\Paths;
use Flarum\Http\UrlGenerator;

class ExportDiskConfig
{
    public function __invoke(Paths $paths, UrlGenerator $url): array
    {
        return [
            'root'   => "$paths->storage/gdpr-exports",
        ];
    }
}
