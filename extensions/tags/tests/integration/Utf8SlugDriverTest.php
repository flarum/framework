<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration;

use Flarum\Tags\Tag;
use Flarum\Tags\TagRepository;
use Flarum\Tags\Utf8SlugDriver;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use PHPUnit\Framework\Attributes\Test;

class Utf8SlugDriverTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use RetrievesRepresentativeTags;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');

        $this->prepareDatabase([
            Tag::class => $this->tags(),
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    /**
     * The reverse map in fromSlugs() keys on the *input* slug string, but the
     * loop keys on the *stored* slug returned by the query. Under a
     * case/accent-insensitive collation (Flarum's default utf8mb4_unicode_ci)
     * the stored slug can differ from the queried input — e.g. searching
     * "Extensions" matches the stored "extensions" row. When it does, the
     * original code hit `$decodedToInput[$tag->slug]` with a key that was never
     * inserted, raising "Undefined array key" (the discuss production error at
     * Utf8SlugDriver.php:73).
     *
     * This drives the driver directly with a repository whose query returns a
     * tag whose slug differs from the input, so the assertion holds on every
     * database driver (SQLite included) regardless of its collation.
     */
    #[Test]
    public function from_slugs_maps_a_differently_cased_stored_slug_without_warning(): void
    {
        $this->app();

        // A real tag whose stored slug is lower-case.
        $stored = Tag::query()->where('slug', 'primary-1')->firstOrFail();

        // The query matched it via a differently-cased input ("Primary-1"),
        // as a case-insensitive collation would. The returned row still carries
        // the stored slug, not the input.
        $repository = new class($stored) extends TagRepository {
            public function __construct(private Tag $stored)
            {
            }

            public function query(): Builder
            {
                $builder = Tag::query();

                // Wrap the builder so get() yields our stored tag regardless of
                // the (case-sensitive, on SQLite) WHERE IN that would otherwise
                // miss the differently-cased input.
                return new class($builder->getQuery(), $this->stored) extends Builder {
                    public function __construct($query, private Tag $stored)
                    {
                        parent::__construct($query);
                        $this->setModel(new Tag());
                    }

                    public function get($columns = ['*']): EloquentCollection
                    {
                        return new EloquentCollection([$this->stored]);
                    }
                };
            }
        };

        $driver = new Utf8SlugDriver($repository);

        $actor = User::find(1);

        // Input slug differs in case from the stored "primary-1".
        $map = $driver->fromSlugs(['Primary-1'], $actor);

        // The collection is keyed by the caller's input slug, and resolves to
        // the stored tag — no "Undefined array key" warning (PHPUnit turns
        // warnings into failures).
        $this->assertTrue($map->has('Primary-1'));
        $this->assertSame($stored->id, $map->get('Primary-1')->id);
    }
}
