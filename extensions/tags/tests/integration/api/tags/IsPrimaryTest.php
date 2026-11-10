<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration\api\tags;

use Flarum\Tags\Tag;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Whether a tag is primary is chosen when it's created. After that it changes
 * only with the tag order (POST /api/tags/order), which moves the tag between
 * lists and sets its position to match: writing the flag alone would leave a
 * primary tag's position behind. Sending the flag back unchanged, as clients
 * that save every attribute do, is fine.
 */
class IsPrimaryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');

        $this->prepareDatabase([
            Tag::class => [
                ['id' => 1, 'name' => 'General', 'slug' => 'general', 'position' => 0, 'is_primary' => true],
                ['id' => 2, 'name' => 'Misc', 'slug' => 'misc', 'position' => null, 'is_primary' => false],
            ],
        ]);
    }

    protected function update(int $id, array $attributes)
    {
        return $this->send(
            $this->request('PATCH', "/api/tags/$id", [
                'authenticatedAs' => 1,
                'json' => ['data' => ['attributes' => $attributes]],
            ])
        );
    }

    #[Test]
    public function a_primary_tag_is_created_after_the_others()
    {
        $response = $this->send(
            $this->request('POST', '/api/tags', [
                'authenticatedAs' => 1,
                'json' => ['data' => ['attributes' => ['name' => 'Support', 'slug' => 'support', 'isPrimary' => true]]],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());

        $tag = Tag::where('slug', 'support')->firstOrFail();

        $this->assertTrue($tag->is_primary);
        $this->assertEquals(1, $tag->position);
    }

    #[Test]
    public function a_secondary_tag_is_created_without_a_position()
    {
        $response = $this->send(
            $this->request('POST', '/api/tags', [
                'authenticatedAs' => 1,
                'json' => ['data' => ['attributes' => ['name' => 'Ideas', 'slug' => 'ideas', 'isPrimary' => false]]],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());

        $tag = Tag::where('slug', 'ideas')->firstOrFail();

        $this->assertFalse($tag->is_primary);
        $this->assertNull($tag->position);
    }

    #[Test]
    public function a_primary_tag_cannot_be_made_secondary_by_update()
    {
        $response = $this->update(1, ['isPrimary' => false]);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('/data/attributes/isPrimary', json_decode($response->getBody(), true)['errors'][0]['source']['pointer']);
        $this->assertTrue(Tag::find(1)->is_primary);
        $this->assertEquals(0, Tag::find(1)->position);
    }

    #[Test]
    public function a_secondary_tag_cannot_be_made_primary_by_update()
    {
        $response = $this->update(2, ['isPrimary' => true]);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('/data/attributes/isPrimary', json_decode($response->getBody(), true)['errors'][0]['source']['pointer']);
        $this->assertFalse(Tag::find(2)->is_primary);
        $this->assertNull(Tag::find(2)->position);
    }

    #[Test]
    public function the_flag_can_be_sent_back_unchanged()
    {
        $primary = $this->update(1, ['name' => 'Everything', 'isPrimary' => true]);
        $secondary = $this->update(2, ['name' => 'Other', 'isPrimary' => false]);

        $this->assertEquals(200, $primary->getStatusCode());
        $this->assertEquals(200, $secondary->getStatusCode());
        $this->assertEquals('Everything', Tag::find(1)->name);
        $this->assertEquals('Other', Tag::find(2)->name);
    }

    #[Test]
    public function a_tag_is_still_updated_without_the_flag()
    {
        $response = $this->update(1, ['name' => 'Everything']);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Everything', Tag::find(1)->name);
        $this->assertTrue(Tag::find(1)->is_primary);
    }
}
