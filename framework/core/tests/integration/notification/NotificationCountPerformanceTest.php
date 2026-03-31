<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\notification;

use Carbon\Carbon;
use Flarum\Extend;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

/**
 * Benchmark / regression test for the notification count performance issue
 * reported in https://github.com/flarum/framework/issues/3952.
 *
 * The unread/new notification counts are serialized on every page load via
 * CurrentUserSerializer. With large notification tables (600k+ rows in
 * production reports) the query is slow because `whereSubjectVisibleTo()`
 * generates one correlated EXISTS (or LEFT JOIN) per subject model class,
 * and when a single user owns a large fraction of the table the user_id
 * index becomes non-selective enough that MySQL abandons it entirely.
 *
 * Two notification types are registered to exercise both subject-model branches:
 *   - discussionRenamed  → Discussion  (core)
 *   - postMentioned      → Post        (custom, registered inline)
 *
 * The seeder creates 1 million notifications across 5000 users:
 *   - USER_ID (2) receives TARGET_USER_NOTIFICATIONS (200k) unread notifications,
 *     split evenly between the two types.
 *   - 4999 noise users each receive ~160 notifications (800k total noise rows).
 *
 * Timing is printed to STDOUT. Run the suite before and after a fix to compare.
 */
class NotificationCountPerformanceTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /** Unread notifications for the user under test (split across two types). */
    private const TARGET_USER_NOTIFICATIONS = 200_000;

    /** Number of noise users (each receives NOISE_NOTIFICATIONS_PER_USER rows). */
    private const NOISE_USER_COUNT = 4_999;

    /** Notifications per noise user: (1M - TARGET_USER_NOTIFICATIONS) / NOISE_USER_COUNT ≈ 160. */
    private const NOISE_NOTIFICATIONS_PER_USER = 160;

    /** Total expected unread count for user under test. */
    private const EXPECTED_COUNT = self::TARGET_USER_NOTIFICATIONS;

    /**
     * Realistic unread count: a user with 200k total notifications but only 500 unread.
     * This models the common production case where most notifications have been read.
     */
    private const REALISTIC_UNREAD_COUNT = 500;

    private const USER_ID = 2;

    /** Number of discussions and posts to spread subject_ids across. */
    private const SUBJECT_COUNT = 200;

    protected function setUp(): void
    {
        parent::setUp();

        // Register a Post-subject notification type so whereSubjectVisibleTo
        // generates a second JOIN/EXISTS branch alongside the core Discussion one.
        $this->extend(
            (new Extend\Notification())
                ->type(PostMentionedBlueprintStub::class, 'postMentionedSerializer', ['alert'])
        );

        $discussions = [];
        for ($i = 1; $i <= self::SUBJECT_COUNT; $i++) {
            $discussions[] = [
                // comment_count > 0 so ScopeDiscussionVisibility doesn't hide it
                // from members who lack discussion.editPosts permission.
                'id' => $i,
                'title' => "Discussion $i",
                'created_at' => Carbon::now()->toDateTimeString(),
                'user_id' => 1,
                'first_post_id' => null,
                'comment_count' => 1,
                'is_private' => 0,
                'last_post_number' => 1,
            ];
        }

        $posts = [];
        for ($i = 1; $i <= self::SUBJECT_COUNT; $i++) {
            $posts[] = [
                'id' => $i,
                'discussion_id' => (($i - 1) % self::SUBJECT_COUNT) + 1,
                'number' => $i,
                'created_at' => Carbon::now()->toDateTimeString(),
                'user_id' => 1,
                'type' => 'comment',
                'content' => '<t><p>post</p></t>',
                'is_private' => 0,
            ];
        }

        $this->prepareDatabase([
            'users' => [
                // id=1 is the admin created by test:setup; seed it explicitly so
                // it exists within the transaction and can be used as from_user_id.
                ['id' => 1, 'username' => 'admin', 'email' => 'admin@machine.local', 'is_email_confirmed' => 1],
                $this->normalUser(),
            ],
            'discussions' => $discussions,
            'posts' => $posts,
        ]);
    }

    /**
     * Bulk-insert all rows after boot, bypassing the slow row-by-row
     * populateDatabase() path.
     *
     * Layout:
     *   - USER_ID: TARGET_USER_NOTIFICATIONS rows, half per type, spread across
     *     SUBJECT_COUNT discussions/posts so subject_id is varied.
     *   - NOISE_USER_COUNT users (ids 3…): NOISE_NOTIFICATIONS_PER_USER rows each.
     */
    private function seed(): void
    {
        $now = Carbon::now()->toDateTimeString();
        $db = $this->database();

        // --- Target user ---
        $half = self::TARGET_USER_NOTIFICATIONS / 2;
        $this->bulkInsert($db, self::USER_ID, 'discussionRenamed', $half, self::SUBJECT_COUNT, $now);
        $this->bulkInsert($db, self::USER_ID, 'postMentioned', $half, self::SUBJECT_COUNT, $now);

        // A read and a deleted row that must NOT appear in the count.
        $db->table('notifications')->insert([
            ['user_id' => self::USER_ID, 'from_user_id' => 1, 'type' => 'discussionRenamed', 'subject_id' => 1, 'data' => null, 'created_at' => $now, 'read_at' => $now, 'is_deleted' => 0],
            ['user_id' => self::USER_ID, 'from_user_id' => 1, 'type' => 'postMentioned',     'subject_id' => 1, 'data' => null, 'created_at' => $now, 'read_at' => null, 'is_deleted' => 1],
        ]);

        // --- Noise users ---
        $firstNoiseId = self::USER_ID + 1;
        $lastNoiseId = $firstNoiseId + self::NOISE_USER_COUNT - 1;

        $chunk = [];
        for ($uid = $firstNoiseId; $uid <= $lastNoiseId; $uid++) {
            $chunk[] = ['id' => $uid, 'username' => "n$uid", 'email' => "n$uid@x.local", 'is_email_confirmed' => 1];
            if (count($chunk) === 500) {
                $db->table('users')->insert($chunk);
                $chunk = [];
            }
        }
        if ($chunk) {
            $db->table('users')->insert($chunk);
        }

        $types = ['discussionRenamed', 'postMentioned'];
        for ($uid = $firstNoiseId; $uid <= $lastNoiseId; $uid++) {
            $type = $types[$uid % 2];
            $this->bulkInsert($db, $uid, $type, self::NOISE_NOTIFICATIONS_PER_USER, self::SUBJECT_COUNT, $now);
        }
    }

    /**
     * Insert $count unread notifications of $type for $userId in chunks of 500,
     * cycling subject_ids across $subjectCount distinct values.
     */
    private function bulkInsert($db, int $userId, string $type, int $count, int $subjectCount, string $now): void
    {
        $this->bulkInsertRows($db, $userId, $type, $count, $subjectCount, $now, null);
    }

    /**
     * Insert $count read notifications for $userId.
     */
    private function bulkInsertRead($db, int $userId, string $type, int $count, int $subjectCount, string $now): void
    {
        $this->bulkInsertRows($db, $userId, $type, $count, $subjectCount, $now, $now);
    }

    private function bulkInsertRows($db, int $userId, string $type, int $count, int $subjectCount, string $now, ?string $readAt): void
    {
        $chunk = [];
        for ($i = 1; $i <= $count; $i++) {
            $chunk[] = [
                'user_id' => $userId,
                'from_user_id' => 1,
                'type' => $type,
                'subject_id' => (($i - 1) % $subjectCount) + 1,
                'data' => null,
                'created_at' => $now,
                'read_at' => $readAt,
                'is_deleted' => 0,
            ];
            if (count($chunk) === 500) {
                $db->table('notifications')->insert($chunk);
                $chunk = [];
            }
        }
        if ($chunk) {
            $db->table('notifications')->insert($chunk);
        }
    }

    /**
     * Realistic seed: user has 200k total notifications but only REALISTIC_UNREAD_COUNT
     * are unread. This models the common production state where a user has been active
     * for a long time and has read most of their notifications. The index fix has the
     * largest impact here because the (user_id, is_deleted, read_at=NULL) prefix is
     * highly selective even when the user owns a large fraction of the table.
     */
    private function seedRealistic(): void
    {
        $now = Carbon::now()->toDateTimeString();
        $db = $this->database();

        $readRows = self::TARGET_USER_NOTIFICATIONS - self::REALISTIC_UNREAD_COUNT;
        $unreadRows = self::REALISTIC_UNREAD_COUNT;
        $half = (int) ($unreadRows / 2);

        // 199,500 read notifications
        $this->bulkInsertRead($db, self::USER_ID, 'discussionRenamed', (int) ($readRows / 2), self::SUBJECT_COUNT, $now);
        $this->bulkInsertRead($db, self::USER_ID, 'postMentioned', (int) ($readRows / 2), self::SUBJECT_COUNT, $now);

        // 500 unread notifications
        $this->bulkInsert($db, self::USER_ID, 'discussionRenamed', $half, self::SUBJECT_COUNT, $now);
        $this->bulkInsert($db, self::USER_ID, 'postMentioned', $unreadRows - $half, self::SUBJECT_COUNT, $now);

        // Noise users (same as main seed)
        $firstNoiseId = self::USER_ID + 1;
        $lastNoiseId = $firstNoiseId + self::NOISE_USER_COUNT - 1;

        $chunk = [];
        for ($uid = $firstNoiseId; $uid <= $lastNoiseId; $uid++) {
            $chunk[] = ['id' => $uid, 'username' => "n$uid", 'email' => "n$uid@x.local", 'is_email_confirmed' => 1];
            if (count($chunk) === 500) {
                $db->table('users')->insert($chunk);
                $chunk = [];
            }
        }
        if ($chunk) {
            $db->table('users')->insert($chunk);
        }

        $types = ['discussionRenamed', 'postMentioned'];
        for ($uid = $firstNoiseId; $uid <= $lastNoiseId; $uid++) {
            $type = $types[$uid % 2];
            $this->bulkInsert($db, $uid, $type, self::NOISE_NOTIFICATIONS_PER_USER, self::SUBJECT_COUNT, $now);
        }
    }

    // -------------------------------------------------------------------------
    // Correctness
    // -------------------------------------------------------------------------

    /**
     * @test
     */
    public function unread_notification_count_is_correct(): void
    {
        $this->app();
        $this->seed();
        $user = User::find(self::USER_ID);
        $this->app()->getContainer()->make('cache.store')->flush();

        $this->assertEquals(self::EXPECTED_COUNT, $user->getUnreadNotificationCount());
    }

    /**
     * @test
     */
    public function new_notification_count_is_correct(): void
    {
        $this->app();
        $this->seed();
        $user = User::find(self::USER_ID);
        $this->assertNull($user->read_notifications_at);
        $this->app()->getContainer()->make('cache.store')->flush();

        $this->assertEquals(self::EXPECTED_COUNT, $user->getNewNotificationCount());
    }

    // -------------------------------------------------------------------------
    // Benchmarks
    // -------------------------------------------------------------------------

    /**
     * @test
     */
    public function unread_count_benchmark(): void
    {
        $this->app();
        $this->seed();
        $user = User::find(self::USER_ID);
        $cache = $this->app()->getContainer()->make('cache.store');

        // Warm run to stabilise caches, then flush so the timed run hits the DB.
        $user->getUnreadNotificationCount();
        $cache->flush();

        $start = microtime(true);
        $count = $user->getUnreadNotificationCount();
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertEquals(self::EXPECTED_COUNT, $count);

        // Capture SQL and run EXPLAIN on the count query.
        $this->database()->enableQueryLog();
        $cache->flush();
        $user->getUnreadNotificationCount();
        $queries = $this->database()->getQueryLog();
        $this->database()->disableQueryLog();

        $sql = $queries[0]['query'] ?? '';
        $bindings = $queries[0]['bindings'] ?? [];
        $totalRows = $this->database()->table('notifications')->count();

        // Run EXPLAIN on the actual query with bindings substituted.
        $explain = $this->database()->select('EXPLAIN '.$sql, $bindings);

        fwrite(STDOUT, sprintf(
            "\n[NotificationCountPerformanceTest] unread count — user rows: %d, table total: %d: %.2f ms\n",
            self::EXPECTED_COUNT,
            $totalRows,
            $elapsed
        ));
        fwrite(STDOUT, sprintf("SQL:\n%s\n\n", $sql));
        fwrite(STDOUT, "EXPLAIN:\n");
        foreach ($explain as $row) {
            fwrite(STDOUT, sprintf(
                "  id=%-2s select_type=%-20s table=%-20s type=%-8s possible_keys=%-40s key=%-40s rows=%-10s Extra=%s\n",
                $row->id,
                $row->select_type,
                $row->table ?? 'NULL',
                $row->type ?? 'NULL',
                $row->possible_keys ?? 'NULL',
                $row->key ?? 'NULL',
                $row->rows ?? 'NULL',
                $row->Extra ?? ''
            ));
        }
        fwrite(STDOUT, "\n");
    }

    /**
     * @test
     */
    public function new_count_benchmark(): void
    {
        $this->app();
        $this->seed();
        $user = User::find(self::USER_ID);
        $cache = $this->app()->getContainer()->make('cache.store');

        $user->getNewNotificationCount();
        $cache->flush();

        $start = microtime(true);
        $count = $user->getNewNotificationCount();
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertEquals(self::EXPECTED_COUNT, $count);

        // Capture SQL and EXPLAIN for the new-count query.
        $this->database()->enableQueryLog();
        $cache->flush();
        $user->getNewNotificationCount();
        $queries = $this->database()->getQueryLog();
        $this->database()->disableQueryLog();

        $sql = $queries[0]['query'] ?? '';
        $bindings = $queries[0]['bindings'] ?? [];
        $explain = $this->database()->select('EXPLAIN '.$sql, $bindings);

        fwrite(STDOUT, sprintf(
            "\n[NotificationCountPerformanceTest] new count — user rows: %d: %.2f ms\n",
            self::EXPECTED_COUNT,
            $elapsed
        ));
        fwrite(STDOUT, sprintf("SQL:\n%s\n\n", $sql));
        fwrite(STDOUT, "EXPLAIN:\n");
        foreach ($explain as $row) {
            fwrite(STDOUT, sprintf(
                "  id=%-2s select_type=%-20s table=%-20s type=%-8s possible_keys=%-40s key=%-40s rows=%-10s Extra=%s\n",
                $row->id,
                $row->select_type,
                $row->table ?? 'NULL',
                $row->type ?? 'NULL',
                $row->possible_keys ?? 'NULL',
                $row->key ?? 'NULL',
                $row->rows ?? 'NULL',
                $row->Extra ?? ''
            ));
        }
        fwrite(STDOUT, "\n");
    }

    /**
     * @test
     *
     * Realistic scenario: user has 200k total notifications but only 500 unread.
     * This is the common production state. The composite index fix has the largest
     * impact here because (user_id, is_deleted, read_at=NULL) is highly selective.
     */
    public function realistic_unread_count_benchmark(): void
    {
        $this->app();
        $this->seedRealistic();
        $user = User::find(self::USER_ID);
        $cache = $this->app()->getContainer()->make('cache.store');

        $user->getUnreadNotificationCount();
        $cache->flush();

        $start = microtime(true);
        $count = $user->getUnreadNotificationCount();
        $elapsed = (microtime(true) - $start) * 1000;

        $this->assertEquals(self::REALISTIC_UNREAD_COUNT, $count);

        $this->database()->enableQueryLog();
        $cache->flush();
        $user->getUnreadNotificationCount();
        $queries = $this->database()->getQueryLog();
        $this->database()->disableQueryLog();

        $sql = $queries[0]['query'] ?? '';
        $bindings = $queries[0]['bindings'] ?? [];
        $totalRows = $this->database()->table('notifications')->count();
        $explain = $this->database()->select('EXPLAIN '.$sql, $bindings);

        fwrite(STDOUT, sprintf(
            "\n[NotificationCountPerformanceTest] realistic unread count — unread: %d, user total: %d, table total: %d: %.2f ms\n",
            self::REALISTIC_UNREAD_COUNT,
            self::TARGET_USER_NOTIFICATIONS,
            $totalRows,
            $elapsed
        ));
        fwrite(STDOUT, "EXPLAIN:\n");
        foreach ($explain as $row) {
            fwrite(STDOUT, sprintf(
                "  id=%-2s select_type=%-20s table=%-20s type=%-8s key=%-40s rows=%-10s Extra=%s\n",
                $row->id,
                $row->select_type,
                $row->table ?? 'NULL',
                $row->type ?? 'NULL',
                $row->key ?? 'NULL',
                $row->rows ?? 'NULL',
                $row->Extra ?? ''
            ));
        }
        fwrite(STDOUT, "\n");
    }
}

// ---------------------------------------------------------------------------
// Inline blueprint stub — registers a Post-subject notification type so the
// query exercises a second subject-model branch (Discussion + Post).
// ---------------------------------------------------------------------------

class PostMentionedBlueprintStub implements BlueprintInterface
{
    public function getFromUser()
    {
        return null;
    }

    public function getSubject()
    {
        return null;
    }

    public function getData()
    {
        return [];
    }

    public static function getType(): string
    {
        return 'postMentioned';
    }

    public static function getSubjectModel(): string
    {
        return Post::class;
    }
}
