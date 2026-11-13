<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration;

use Flarum\Tags\Tag;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * A tag's kind is read from `is_primary`, never from `position`: secondary
 * tags have no position in 2.x, but that must not be what makes them
 * secondary.
 */
class TagKindTest extends TestCase
{
    use RetrievesRepresentativeTags;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');

        $this->prepareDatabase([
            'tags' => $this->tags(),
        ]);
    }

    private function tag(int $id): Tag
    {
        $this->app();

        return Tag::query()->findOrFail($id);
    }

    #[Test]
    public function a_top_level_primary_tag_is_a_primary_parent()
    {
        $tag = $this->tag(1);

        $this->assertTrue($tag->isPrimary());
        $this->assertFalse($tag->isSecondary());
        $this->assertFalse($tag->isChild());
        $this->assertTrue($tag->isPrimaryParent());
    }

    #[Test]
    public function a_child_of_a_primary_tag_is_primary_but_not_a_primary_parent()
    {
        $tag = $this->tag(3);

        $this->assertTrue($tag->isPrimary());
        $this->assertFalse($tag->isSecondary());
        $this->assertTrue($tag->isChild());
        $this->assertFalse($tag->isPrimaryParent());
    }

    #[Test]
    public function a_secondary_tag_is_secondary()
    {
        $tag = $this->tag(9);

        $this->assertFalse($tag->isPrimary());
        $this->assertTrue($tag->isSecondary());
        $this->assertFalse($tag->isChild());
        $this->assertFalse($tag->isPrimaryParent());
    }

    #[Test]
    public function a_secondary_tag_with_a_position_stays_secondary()
    {
        $this->app();

        Tag::query()->whereKey(9)->update(['position' => 3]);

        $tag = Tag::query()->findOrFail(9);

        $this->assertTrue($tag->isSecondary());
        $this->assertFalse($tag->isPrimaryParent());
        $this->assertNotContains(9, Tag::query()->primary()->pluck('id')->map(fn ($id) => (int) $id)->all());
        $this->assertContains(9, Tag::query()->secondary()->pluck('id')->map(fn ($id) => (int) $id)->all());
    }

    #[Test]
    public function the_scopes_split_tags_by_kind()
    {
        $this->app();

        $this->assertEqualsCanonicalizing(
            [1, 2, 3, 4, 5, 6, 7, 8, 12, 13, 14],
            Tag::query()->primary()->pluck('id')->map(fn ($id) => (int) $id)->all()
        );

        $this->assertEqualsCanonicalizing(
            [9, 10, 11],
            Tag::query()->secondary()->pluck('id')->map(fn ($id) => (int) $id)->all()
        );
    }
}
