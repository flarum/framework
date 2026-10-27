<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\database;

use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * The discussion list can be sorted by title, and that sort is only usable
 * because the column carries an ordinary index.
 *
 * This is worth asserting because losing the index breaks nothing visibly. The
 * sort keeps returning correct results; it just stops using an index and reads
 * the whole table instead, which stays unnoticeable on a small forum and is
 * felt on every page of a large one. A test is the only thing standing between
 * that and a migration someone tidies away later.
 *
 * Note that MySQL and MariaDB already carry a FULLTEXT index on this column for
 * search. That one cannot order rows — it is a list of the words each title
 * contains — so its presence says nothing about whether sorting works.
 */
class DiscussionTitleIndexTest extends TestCase
{
    protected function schema()
    {
        return $this->app()
            ->getContainer()
            ->make('db')
            ->getSchemaBuilder();
    }

    #[Test]
    public function the_title_column_is_indexed_for_sorting()
    {
        // Matched on the column rather than the index name, which varies with
        // the table prefix a site has configured.
        $this->assertTrue(
            $this->schema()->hasIndex('discussions', ['title']),
            'discussions.title has no index, so sorting by title reads the whole table.'
        );
    }

    #[Test]
    public function the_sorting_index_is_not_the_fulltext_one()
    {
        $indexes = collect($this->schema()->getIndexes('discussions'))
            ->filter(fn (array $index) => $index['columns'] === ['title']);

        $this->assertTrue(
            $indexes->contains(fn (array $index) => $index['type'] !== 'fulltext'),
            'The only index on discussions.title is a FULLTEXT one, which cannot satisfy ORDER BY.'
        );
    }
}
