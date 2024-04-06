<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search;

use Flarum\Database\AbstractModel;

interface IndexerInterface
{
    public static function index(): string;

    /**
     * @param AbstractModel[] $models
     */
    public function save(array $models): void;

    /**
     * @param AbstractModel[] $models
     */
    public function delete(array $models): void;

    /**
     * Build the index from scratch.
     */
    public function build(): void;

    /**
     * Flush the index.
     */
    public function flush(): void;
}
