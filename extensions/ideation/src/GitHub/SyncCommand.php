<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\GitHub;

use Flarum\Console\AbstractCommand;

/**
 * php flarum ideation:github-sync — refresh linked GitHub items the webhook
 * has not confirmed lately. Scheduled hourly; a no-op while the integration
 * is off, and capped at 50 items a run so a big forum stays inside GitHub's
 * rate limit (60 an hour without a token, 5,000 with one).
 */
class SyncCommand extends AbstractCommand
{
    public function __construct(
        protected Config $config,
        protected Links $links
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('ideation:github-sync')
            ->setDescription('Refresh the GitHub issues, pull requests and milestones linked to ideas.');
    }

    protected function fire(): int
    {
        if (! $this->config->enabled()) {
            $this->info('The GitHub integration is off.');

            return 0;
        }

        $this->info('Refreshed '.$this->links->sync($this->config->token() === '' ? 20 : 50).' linked items.');

        return 0;
    }
}
