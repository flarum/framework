<?php

namespace Flarum\Search;

use Flarum\Database\AbstractModel;

interface IndexerInterface
{
    static function index(): string;

    /**
     * @param AbstractModel[] $models
     */
    function save(array $models): void;

    /**
     * @param AbstractModel[] $models
     */
    function delete(array $models): void;

    /**
     * Build the index from scratch.
     */
    function build(): void;

    /**
     * Flush the index.
     */
    function flush(): void;
}
