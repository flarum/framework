<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Console;

use Flarum\Ai\Models\ModelHealth;
use Flarum\Console\AbstractCommand;
use Illuminate\Contracts\Container\Container;

/**
 * @internal
 */
class CheckModelsCommand extends AbstractCommand
{
    /**
     * Commands are made whenever the console starts, so what this one needs is
     * only made when it runs: it reads the features every extension registered.
     */
    public function __construct(
        protected Container $container
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('ai:check-models')
            ->setDescription('Check that the AI models features use are still offered by their providers.');
    }

    protected function fire(): int
    {
        ['issues' => $issues, 'unchecked' => $unchecked, 'unlisted' => $unlisted] = $this->container->make(ModelHealth::class)->check();

        foreach ($issues as $issue) {
            $this->info("{$issue['connection']}/{$issue['model']}: {$issue['status']}".($issue['retiresAt'] ? ", retires {$issue['retiresAt']}" : ''));
        }

        foreach ($unlisted as $connection) {
            $this->info("The models of the connection \"$connection\" can't be checked: its provider doesn't list them.");
        }

        foreach ($unchecked as $connection) {
            $this->error("The models of the connection \"$connection\" could not be checked: its provider could not be asked for them.");
        }

        if ($unchecked !== []) {
            return 1;
        }

        if ($issues === []) {
            $this->info($unlisted === [] ? 'Every model in use is offered.' : 'Every model in use that could be checked is offered.');
        }

        return 0;
    }
}
