<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Formatter\Formatter;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Resolving @mentions must not scan the whole mentioned-users collection once
 * per mention: that is O(N^2) in the number of distinct users mentioned, and a
 * single post can carry thousands of mentions. This checks that a post packed
 * with mentions still resolves every one of them correctly and does so in time
 * that stays roughly linear rather than quadratic in the mention count.
 */
class ManyUserMentionsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-mentions');

        $users = [$this->normalUser()];
        for ($i = 100; $i < 100 + self::USER_COUNT; $i++) {
            $users[] = ['id' => $i, 'username' => "mentionee$i", 'email' => "mentionee$i@machine.local", 'is_email_confirmed' => 1];
        }

        $this->prepareDatabase([
            User::class => $users,
            Discussion::class => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t></t>'],
            ],
        ]);

        $this->setting('flarum-mentions.allow_username_format', '1');
    }

    private const USER_COUNT = 400;

    private function mentionText(int $count): string
    {
        $parts = [];
        for ($i = 100; $i < 100 + $count; $i++) {
            $parts[] = "@mentionee$i";
        }

        return implode(' ', $parts);
    }

    private function parseTime(int $count): float
    {
        /** @var Formatter $formatter */
        $formatter = $this->app()->getContainer()->make(Formatter::class);
        $post = Post::query()->find(1);
        $actor = User::query()->find(2);

        $start = microtime(true);
        $xml = $formatter->parse($this->mentionText($count), $post, $actor);
        $elapsed = microtime(true) - $start;

        // Every mention must actually resolve to a USERMENTION tag, or a
        // "fast" run that silently stopped resolving would pass on time alone.
        $this->assertSame($count, substr_count($xml, '<USERMENTION'), "expected $count resolved mentions");

        return $elapsed;
    }

    #[Test]
    public function resolution_scales_roughly_linearly_with_mention_count()
    {
        // Warm up so the first-call boot cost is not attributed to N.
        $this->parseTime(1);

        $half = $this->parseTime(self::USER_COUNT / 2);
        $full = $this->parseTime(self::USER_COUNT);

        // Quadratic resolution makes doubling the input ~4x the work; linear
        // makes it ~2x. Allow generous headroom for the linear per-mention
        // costs and CI jitter, but 3x still fails a true O(N^2) scan (which
        // measures ~4x here) while passing the indexed lookup (~2x).
        $this->assertLessThan(
            $half * 3,
            $full,
            sprintf('resolving %d mentions took %.3fs vs %.3fs for %d — that is superlinear, i.e. the O(N^2) scan', self::USER_COUNT, $full, $half, self::USER_COUNT / 2)
        );
    }
}
