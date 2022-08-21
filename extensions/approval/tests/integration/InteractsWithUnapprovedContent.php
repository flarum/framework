<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Approval\Tests\integration;

use Carbon\Carbon;

trait InteractsWithUnapprovedContent
{
    protected function prepareUnapprovedDatabaseContent()
    {
        $this->prepareDatabase([
            'users' => [
                ['id' => 1, 'username' => 'Muralf', 'email' => 'muralf@machine.local', 'is_email_confirmed' => 1],
                $this->normalUser(),
                ['id' => 3, 'username' => 'acme', 'email' => 'acme@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'luceos', 'email' => 'luceos@machine.local', 'is_email_confirmed' => 1],
            ],
            'discussions' => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 4, 'first_post_id' => 1, 'comment_count' => 1, 'is_approved' => 1, 'is_private' => 0],
                ['id' => 2, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 4, 'first_post_id' => 2, 'comment_count' => 1, 'is_approved' => 0, 'is_private' => 1],
                ['id' => 3, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 4, 'first_post_id' => 3, 'comment_count' => 1, 'is_approved' => 0, 'is_private' => 1],
                ['id' => 4, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 4, 'first_post_id' => 4, 'comment_count' => 1, 'is_approved' => 1, 'is_private' => 0],
                ['id' => 5, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 4, 'first_post_id' => 5, 'comment_count' => 1, 'is_approved' => 1, 'is_private' => 0],
                ['id' => 6, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 2, 'first_post_id' => 6, 'comment_count' => 1, 'is_approved' => 0, 'is_private' => 1],
                ['id' => 7, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 4, 'first_post_id' => 7, 'comment_count' => 1, 'is_approved' => 1, 'is_private' => 0],

                // Normal discussion with first post being private (also means comment_count = 0).
                ['id' => 8, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 4, 'first_post_id' => 12, 'comment_count' => 0, 'is_approved' => 1, 'is_private' => 0],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_private' => 0, 'is_approved' => 1, 'number' => 1],
                ['id' => 2, 'discussion_id' => 2, 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_private' => 0, 'is_approved' => 1, 'number' => 1],
                ['id' => 3, 'discussion_id' => 3, 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_private' => 0, 'is_approved' => 1, 'number' => 1],
                ['id' => 4, 'discussion_id' => 4, 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_private' => 0, 'is_approved' => 1, 'number' => 1],
                ['id' => 5, 'discussion_id' => 5, 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_private' => 0, 'is_approved' => 1, 'number' => 1],
                ['id' => 6, 'discussion_id' => 6, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_private' => 0, 'is_approved' => 1, 'number' => 1],
                ['id' => 7, 'discussion_id' => 7, 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_private' => 0, 'is_approved' => 1, 'number' => 1],

                ['id' => 8, 'discussion_id' => 7, 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_private' => 0, 'is_approved' => 1, 'number' => 2],
                ['id' => 9, 'discussion_id' => 7, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_private' => 1, 'is_approved' => 0, 'number' => 3],
                ['id' => 10, 'discussion_id' => 7, 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_private' => 0, 'is_approved' => 1, 'number' => 4],
                ['id' => 11, 'discussion_id' => 7, 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_private' => 1, 'is_approved' => 0, 'number' => 5],

                // First post of a normal discussion being private.
                ['id' => 12, 'discussion_id' => 8, 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_private' => 1, 'is_approved' => 0, 'number' => 1],
            ],
            'groups' => [
                ['id' => 100, 'name_singular' => 'Acme', 'name_plural' => 'Acme', 'is_hidden' => 0]
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 100]
            ],
            'group_permission' => [
                ['permission' => 'discussion.approvePosts', 'group_id' => 100]
            ]
        ]);
    }
}
