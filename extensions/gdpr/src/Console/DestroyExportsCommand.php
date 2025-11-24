<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Console;

use Flarum\Gdpr\Exporter;
use Flarum\Gdpr\Models\Export;
use Illuminate\Console\Command;

class DestroyExportsCommand extends Command
{
    protected $signature = 'gdpr:destroy-exports';
    protected $description = 'Deletes exported data sets.';

    public function handle(Exporter $exporter): void
    {
        Export::destroyable()
            ->each(function (Export $export) use ($exporter) {
                $exporter->destroy($export);
            });
    }
}
