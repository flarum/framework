<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\Info\Section;

use Flarum\Foundation\Info\RendererInterface;
use Flarum\Foundation\Info\SectionInterface;
use Flarum\Settings\SettingsRepositoryInterface;

class ServiceUsers implements SectionInterface
{
    public function __construct(protected SettingsRepositoryInterface $settings)
    {
    }

    public function __invoke(RendererInterface $renderer): void
    {
        $rows = [
            ['Web user', $this->settings->get('core.debug.web_user') ?? 'visit admin to identify']
        ];

        if (php_sapi_name() === 'cli') {
            $rows[] = [
                'Current user',
                posix_getpwuid(posix_geteuid())['name']
            ];
        }

        $renderer->table([
            ['Service/process users'],
            ['Type', 'User']
        ], $rows);
    }
}
