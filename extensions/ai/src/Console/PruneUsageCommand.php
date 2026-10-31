<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Console;

use Flarum\Ai\Usage\Ledger;
use Flarum\Console\AbstractCommand;

/**
 * @internal
 */
class PruneUsageCommand extends AbstractCommand
{
    public function __construct(
        protected Ledger $ledger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('ai:prune-usage')
            ->setDescription('Delete AI usage records older than '.Ledger::KEEP_DAYS.' days.');
    }

    protected function fire(): int
    {
        $this->info("Deleted {$this->ledger->prune()} AI usage records.");

        return 0;
    }
}
