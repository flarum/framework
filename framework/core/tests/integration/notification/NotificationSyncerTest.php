<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\notification;

use Carbon\Carbon;
use Flarum\Api\Serializer\BasicDiscussionSerializer;
use Flarum\Api\Serializer\BasicPostSerializer;
use Flarum\Database\AbstractModel;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\Notification;
use Flarum\Notification\NotificationSyncer;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

class NotificationSyncerTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'Receiver', 'email' => 'receiver@machine.local', 'is_email_confirmed' => 1],
            ],
            'discussions' => [
                ['id' => 1, 'title' => 'Public discussion', 'created_at' => Carbon::parse('2021-11-01 13:00:00')->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 2, 'is_private' => 0, 'last_post_number' => 2],

                ['id' => 2, 'title' => 'Private discussion', 'created_at' => Carbon::parse('2021-11-01 13:00:00')->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 3, 'comment_count' => 2, 'is_private' => 1, 'last_post_number' => 2],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'created_at' => Carbon::parse('2021-11-01 13:00:00')->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t></t>', 'is_private' => 0],
                ['id' => 2, 'discussion_id' => 1, 'number' => 2, 'created_at' => Carbon::parse('2021-11-01 13:00:03')->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t></t>', 'is_private' => 1],

                ['id' => 3, 'discussion_id' => 2, 'number' => 1, 'created_at' => Carbon::parse('2021-11-01 13:00:00')->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t></t>', 'is_private' => 0],
            ],
        ]);
    }

    /**
     * @dataProvider visibleSubjectsProvider
     * @param class-string<AbstractModel> $subjectClass
     * @test
     */
    public function can_receive_notification_for_visible_subjects(string $subjectClass, int $subjectId, string $serializer)
    {
        $this->expect_notification_count_from_sending_notification_type_with_subject(
            2,
            $subjectClass,
            $subjectId,
            $serializer
        );
    }


    /**
     * @dataProvider invisibleSubjectsProvider
     * @test
     */
    public function cannot_receive_notification_for_restricted_subjects(string $subjectClass, int $subjectId, string $serializer)
    {
        $this->expect_notification_count_from_sending_notification_type_with_subject(
            0,
            $subjectClass,
            $subjectId,
            $serializer
        );
    }

    /**
     * @param class-string<AbstractModel> $subjectClass
     */
    protected function expect_notification_count_from_sending_notification_type_with_subject(int $count, string $subjectClass, int $subjectId, string $serializer)
    {
        CustomNotificationType::$subjectModel = $subjectClass;

        $this->extend(
            (new Extend\Notification())
                ->type(CustomNotificationType::class, $serializer, ['alert'])
        );

        /** @var NotificationSyncer $syncer */
        $syncer = $this->app()->getContainer()->make(NotificationSyncer::class);

        $subject = $subjectClass::query()->find($subjectId);

        $syncer->sync(
            $blueprint = new CustomNotificationType($subject),
            User::query()
                ->whereIn('id', [1, 3])
                ->get()
                ->all()
        );

        $this->assertEquals(
            $count,
            Notification::query()
                ->matchingBlueprint($blueprint)
                ->whereSubject($subject)
                ->count()
        );
    }

    protected function visibleSubjectsProvider()
    {
        return [
            [Post::class, 1, BasicPostSerializer::class],
            [Discussion::class, 1, BasicDiscussionSerializer::class],
        ];
    }

    protected function invisibleSubjectsProvider()
    {
        return [
            [Post::class, 2, BasicPostSerializer::class],
            [Discussion::class, 2, BasicDiscussionSerializer::class],
            [Post::class, 3, BasicPostSerializer::class],
        ];
    }
}

class CustomNotificationType implements BlueprintInterface
{
    protected $subject;
    public static $subjectModel;

    public function __construct($subject)
    {
        $this->subject = $subject;
    }

    public function getFromUser()
    {
        return null;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function getData()
    {
        return [];
    }

    public static function getType()
    {
        return 'customNotificationType';
    }

    public static function getSubjectModel()
    {
        return self::$subjectModel;
    }
}
