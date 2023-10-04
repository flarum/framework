<?php

namespace Flarum\Search\Listener;

use Flarum\Database\AbstractModel;
use Flarum\Search\Job\IndexJob;
use Flarum\Search\SearchManager;
use Illuminate\Contracts\Queue\Queue;

class ModelObserver
{
    public function __construct(
        protected SearchManager $search,
        protected Queue $queue
    ) {
    }

    public function created(AbstractModel $model): void
    {
        $this->runIndexJob($model, IndexJob::SAVE);
    }

    public function updated(AbstractModel $model): void
    {
        $this->runIndexJob($model, IndexJob::SAVE);
    }

    public function hidden(AbstractModel $model): void
    {
        $this->runIndexJob($model, IndexJob::DELETE);
    }

    public function deleted(AbstractModel $model): void
    {
        $this->runIndexJob($model, IndexJob::DELETE);
    }

    public function forceDeleted(AbstractModel $model): void
    {
        $this->runIndexJob($model, IndexJob::DELETE);
    }

    public function restored(AbstractModel $model): void
    {
        $this->runIndexJob($model, IndexJob::SAVE);
    }

    private function runIndexJob(AbstractModel $model, string $operation): void
    {
        if ($this->search->indexable($model)) {
            foreach ($this->search->indexers($model) as $indexerClass) {
                $queue = property_exists($indexerClass, 'queue') ? $indexerClass::$queue : null;

                $this->queue->pushOn($queue, new IndexJob($indexerClass, [$model], $operation));
            }
        }
    }
}
