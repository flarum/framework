<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Group\Group;
use Flarum\Post\AbstractEventPost;
use Flarum\Post\CommentPost;
use Flarum\Post\DiscussionRenamedPost;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class ModelTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    protected function prepPostsHierarchy()
    {
        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 1, 'title' => 'Discussion with post', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1, 'is_private' => 0],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'discussionRenamed', 'content' => '<t><p>can i haz relationz?</p></t>'],
            ],
        ]);
    }

    #[Test]
    public function custom_relationship_does_not_exist_by_default()
    {
        $this->app();

        $user = User::find(1);

        $this->expectException(\BadMethodCallException::class);
        $user->customRelation();
    }

    #[Test]
    public function custom_hasOne_relationship_exists_if_added()
    {
        $this->extend(
            (new Extend\Model(User::class))
                ->hasOne('customRelation', Discussion::class, 'user_id')
        );

        $this->app();

        $user = User::find(1);

        $this->assertEquals([], $user->customRelation()->get()->toArray());
    }

    #[Test]
    public function custom_hasMany_relationship_exists_if_added()
    {
        $this->extend(
            (new Extend\Model(User::class))
                ->hasMany('customRelation', Discussion::class, 'user_id')
        );

        $this->app();

        $user = User::find(1);

        $this->assertEquals([], $user->customRelation()->get()->toArray());
    }

    #[Test]
    public function custom_belongsTo_relationship_exists_if_added()
    {
        $this->extend(
            (new Extend\Model(User::class))
                ->belongsTo('customRelation', Discussion::class, 'user_id')
        );

        $this->app();

        $user = User::find(1);

        $this->assertEquals([], $user->customRelation()->get()->toArray());
    }

    #[Test]
    public function custom_relationship_exists_if_added()
    {
        $this->extend(
            (new Extend\Model(User::class))
                ->relationship('customRelation', function (User $user) {
                    return $user->hasMany(Discussion::class, 'user_id');
                })
        );

        $this->app();

        $user = User::find(1);

        $this->assertEquals([], $user->customRelation()->get()->toArray());
    }

    #[Test]
    public function custom_relationship_can_be_invokable_class()
    {
        $this->extend(
            (new Extend\Model(User::class))
                ->relationship('customRelation', CustomRelationClass::class)
        );

        $this->app();

        $user = User::find(1);

        $this->assertEquals([], $user->customRelation()->get()->toArray());
    }

    #[Test]
    public function custom_relationship_exists_and_can_return_instances_if_added()
    {
        $this->extend(
            (new Extend\Model(User::class))
                ->relationship('customRelation', function (User $user) {
                    return $user->hasMany(Discussion::class, 'user_id');
                })
        );

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1]
            ]
        ]);

        $this->app();

        $user = User::find(1);

        $this->assertNotEquals([], $user->customRelation()->get()->toArray());
        $this->assertStringContainsString(json_encode(__CLASS__), json_encode($user->customRelation()->get()));
    }

    #[Test]
    public function custom_relationship_is_inherited_to_child_classes()
    {
        $this->extend(
            (new Extend\Model(Post::class))
                ->belongsTo('ancestor', Discussion::class, 'discussion_id')
        );

        $this->prepPostsHierarchy();

        $this->app();

        $post = CommentPost::find(1);

        $this->assertInstanceOf(Discussion::class, $post->ancestor);
        $this->assertEquals(1, $post->ancestor->id);
    }

    #[Test]
    public function custom_relationship_prioritizes_child_classes_within_2_parent_classes()
    {
        $this->extend(
            (new Extend\Model(Post::class))
                ->belongsTo('ancestor', User::class, 'user_id'),
            (new Extend\Model(AbstractEventPost::class))
                ->belongsTo('ancestor', Discussion::class, 'discussion_id')
        );

        $this->prepPostsHierarchy();

        $this->app();

        $post = DiscussionRenamedPost::find(1);

        $this->assertInstanceOf(Discussion::class, $post->ancestor);
        $this->assertEquals(1, $post->ancestor->id);
    }

    #[Test]
    public function custom_relationship_prioritizes_child_classes_within_child_class_and_immediate_parent()
    {
        $this->extend(
            (new Extend\Model(AbstractEventPost::class))
                ->belongsTo('ancestor', Discussion::class, 'discussion_id'),
            (new Extend\Model(DiscussionRenamedPost::class))
                ->belongsTo('ancestor', User::class, 'user_id')
        );

        $this->prepPostsHierarchy();

        $this->app();

        $post = DiscussionRenamedPost::find(1);

        $this->assertInstanceOf(User::class, $post->ancestor);
        $this->assertEquals(2, $post->ancestor->id);
    }

    #[Test]
    public function custom_relationship_does_not_exist_if_added_to_unrelated_model()
    {
        $this->extend(
            (new Extend\Model(User::class))
                ->relationship('customRelation', function (User $user) {
                    return $user->hasMany(Discussion::class, 'user_id');
                })
        );

        $this->app();

        $group = Group::find(1);

        $this->expectException(\BadMethodCallException::class);
        $group->customRelation();
    }

    #[Test]
    public function custom_default_attribute_doesnt_exist_if_not_set()
    {
        $group = new Group;

        $this->app();

        $this->assertNotEquals('Custom Default', $group->name_singular);
    }

    #[Test]
    public function custom_default_attribute_works_if_set()
    {
        $this->extend(
            (new Extend\Model(Group::class))
                ->default('name_singular', 'Custom Default')
        );

        $this->app();

        $group = new Group;

        $this->assertEquals('Custom Default', $group->name_singular);
    }

    #[Test]
    public function custom_default_attribute_evaluated_at_runtime_if_callable()
    {
        $this->extend(
            (new Extend\Model(Group::class))
                ->default('counter', function (Group $group) {
                    static $counter = 0;

                    return ++$counter;
                })
        );

        $this->app();

        Group::boot();

        $group1 = new Group();
        $group2 = new Group();

        $this->assertEquals(3, $group1->counter);
        $this->assertEquals(4, $group2->counter);
    }

    #[Test]
    public function custom_default_attribute_is_inherited_to_child_classes()
    {
        $this->extend(
            (new Extend\Model(Post::class))
                ->default('answer', 42)
        );

        $this->app();

        $post = new CommentPost;

        $this->assertEquals(42, $post->answer);
    }

    #[Test]
    public function custom_default_attribute_inheritance_prioritizes_child_class()
    {
        $this->extend(
            (new Extend\Model(Post::class))
                ->default('answer', 'dont do this'),
            (new Extend\Model(AbstractEventPost::class))
                ->default('answer', 42),
            (new Extend\Model(DiscussionRenamedPost::class))
                ->default('answer', 'ni!')
        );

        $this->app();

        $post = new ModelTestCustomPost;

        $this->assertEquals(42, $post->answer);

        $commentPost = new DiscussionRenamedPost;

        $this->assertEquals('ni!', $commentPost->answer);
    }

    #[Test]
    public function custom_default_attribute_doesnt_work_if_set_on_unrelated_model()
    {
        $this->extend(
            (new Extend\Model(Group::class))
                ->default('name_singular', 'Custom Default')
        );

        $this->app();

        $user = new User;

        $this->assertNotEquals('Custom Default', $user->name_singular);
    }

    #[Test]
    public function custom_cast_attribute_doesnt_exist_by_default()
    {
        $post = new Post;

        $this->app();

        $this->assertFalse($post->hasCast('custom'));
    }

    #[Test]
    public function custom_cast_attribute_can_be_set()
    {
        $this->extend(
            (new Extend\Model(Post::class))
                ->cast('custom', 'datetime')
        );

        $this->app();

        $post = new Post;

        $this->assertTrue($post->hasCast('custom', 'datetime'));
    }

    #[Test]
    public function custom_cast_attribute_is_inherited_to_child_classes()
    {
        $this->extend(
            (new Extend\Model(Post::class))
                ->cast('custom', 'boolean')
        );

        $this->app();

        $post = new CommentPost;

        $this->assertTrue($post->hasCast('custom', 'boolean'));
    }

    #[Test]
    public function custom_cast_attribute_doesnt_work_if_set_on_unrelated_model()
    {
        $this->extend(
            (new Extend\Model(Post::class))
                ->cast('custom', 'integer')
        );

        $this->app();

        $discussion = new Discussion;

        $this->assertFalse($discussion->hasCast('custom', 'integer'));
    }
}

class ModelTestCustomPost extends AbstractEventPost
{
    public static string $type = 'customPost';
}

class CustomRelationClass
{
    public function __invoke(User $user)
    {
        return $user->hasMany(Discussion::class, 'user_id');
    }
}
