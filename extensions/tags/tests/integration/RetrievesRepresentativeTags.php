<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration;

trait RetrievesRepresentativeTags
{
    protected function tags()
    {
        return [
            ['id' => 1, 'name' => 'Primary 1', 'slug' => 'primary-1', 'position' => 0, 'parent_id' => null],
            ['id' => 2, 'name' => 'Primary 2', 'slug' => 'primary-2', 'position' => 1, 'parent_id' => null],
            ['id' => 3, 'name' => 'Primary 2 Child 1', 'slug' => 'primary-2-child-1', 'position' => 2, 'parent_id' => 2],
            ['id' => 4, 'name' => 'Primary 2 Child 2', 'slug' => 'primary-2-child-2', 'position' => 3, 'parent_id' => 2],
            ['id' => 5, 'name' => 'Primary 2 Child Restricted', 'slug' => 'primary-2-child-restricted', 'position' => 4, 'parent_id' => 2, 'is_restricted' => true],
            ['id' => 6, 'name' => 'Primary Restricted', 'slug' => 'primary-restricted', 'position' => 5, 'parent_id' => null, 'is_restricted' => true],
            ['id' => 7, 'name' => 'Primary Restricted Child 1', 'slug' => 'primary-restricted-child-1', 'position' => 6, 'parent_id' => 6],
            ['id' => 8, 'name' => 'Primary Restricted Child Restricted', 'slug' => 'primary-restricted-child-restricted', 'position' => 7, 'parent_id' => 6, 'is_restricted' => true],
            ['id' => 9, 'name' => 'Secondary 1', 'slug' => 'secondary-1', 'position' => null, 'parent_id' => null],
            ['id' => 10, 'name' => 'Secondary 2', 'slug' => 'secondary-2', 'position' => null, 'parent_id' => null],
            ['id' => 11, 'name' => 'Secondary Restricted', 'slug' => 'secondary-restricted', 'position' => null, 'parent_id' => null, 'is_restricted' => true],
            ['id' => 12, 'name' => 'Primary Restricted 2', 'slug' => 'primary-2-restricted', 'position' => 100, 'parent_id' => null, 'is_restricted' => true],
            ['id' => 13, 'name' => 'Primary Restricted 2 Child 1', 'slug' => 'primary-2-restricted-child-1', 'position' => 101, 'parent_id' => 12],
            ['id' => 14, 'name' => 'Primary Restricted 3', 'slug' => 'primary-3-restricted', 'position' => 102, 'parent_id' =>null, 'is_restricted' => true],
        ];
    }
}
