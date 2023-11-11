<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search\Job;

use Flarum\Database\AbstractModel;
use Flarum\Queue\AbstractJob;
use Illuminate\Contracts\Container\Container;

class IndexJob extends AbstractJob
{
    public const SAVE = 'save';
    public const DELETE = 'delete';

    public function __construct(
        protected string $indexerClass,
        /**
         * @var AbstractModel[]
         */
        protected array $models,
        /**
         * @var string{'save'|'delete'}
         */
        protected string $operation,
    ) {
    }

    public function handle(Container $container): void
    {
        if (empty($this->models)) {
            return;
        }

        $indexer = $container->make($this->indexerClass);

        $indexer->{$this->operation}($this->models);
    }
}
