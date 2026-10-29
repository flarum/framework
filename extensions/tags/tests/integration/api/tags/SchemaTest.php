<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration\api\tags;

use Flarum\Testing\integration\TestCase;
use Illuminate\Database\Schema\Builder;
use PHPUnit\Framework\Attributes\Test;

class SchemaTest extends TestCase
{
    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');
    }

    #[Test]
    public function legacy_background_columns_are_not_present_on_tags_table()
    {
        $schema = $this->app()->getContainer()->make(Builder::class);

        $this->assertFalse($schema->hasColumn('tags', 'background_path'));
        $this->assertFalse($schema->hasColumn('tags', 'background_mode'));
    }
}
