<?php namespace Flarum\Core\Support\Seeders;

use Illuminate\Database\Seeder;
use DB;

use Flarum\Core\Discussions\Discussion;
use Flarum\Core\Posts\Post;
use Flarum\Core\Users\User;
use Flarum\Core\Discussions\DiscussionState;

class DiscussionTableSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = \Faker\Factory::create();

        $users = User::count();

        for ($i = 0; $i < 100; $i++) {
            $posts_count = $i == 1 ? 400 : rand(1, rand(1, rand(1, 100)));
            $discussion = Discussion::create([
                'title'         => str_replace("'", '', rtrim($faker->realText(rand(20, 80)), '.')),
                'start_time'    => $faker->dateTimeThisYear,
                'start_user_id' => rand(1, $users)
            ]);
            $discussion->posts_count = $posts_count;

            $post = Post::create([
                'discussion_id' => $discussion->id,
                'number'        => 1,
                'time'          => $discussion->start_time,
                'user_id'       => $discussion->start_user_id,
                'type'          => 'comment',
                'content'       => $faker->realText(rand(100, 1000)),
                'html_content'       => $faker->realText(rand(100, 1000))
            ]);

            $discussion->start_post_id = $post->id;

            $discussion->last_time        = $post->time;
            $discussion->last_user_id     = $post->user_id;
            $discussion->last_post_id     = $post->id;
            $discussion->last_post_number = $post->number;
            $discussion->number_index     = $post->number;

            $lastPost = null;
            $count = $posts_count;
            $posts = [];
            $startTime = $discussion->start_time;
            $numberOffset = 0;

            for ($j = 0; $j < $count - 1; $j++) {
                if (rand(1, 100) == 1) {
                    $discussion->posts_count--;

                    $post = Post::create([
                        'discussion_id' => $discussion->id,
                        'number'        => $j + 2 + $numberOffset,
                        'time'          => $startTime = date_add($startTime, date_interval_create_from_date_string('1 second')),
                        'user_id'       => rand(1, $users),
                        'type'          => 'title',
                        'content'       => $discussion->title,
                        'html_content'       => $discussion->title
                    ]);
                } else {
                    $edited = rand(1, 20) == 1;
                    $deleted = rand(1, 100) == 1;

                    if ($deleted) {
                        $discussion->posts_count--;
                    }

                    $post = Post::create([
                        'discussion_id' => $discussion->id,
                        'number'        => $j + 2 + $numberOffset,
                        'time'          => $startTime = date_add($startTime, date_interval_create_from_date_string('1 second')),
                        'user_id'       => rand(1, $users),
                        'type'          => 'comment',
                        'content'       => $faker->realText(rand(50, 500)),
                        'html_content'       => $faker->realText(rand(50, 500)),
                        'edit_time'     => $edited ? $startTime = date_add($startTime, date_interval_create_from_date_string('1 second')) : null,
                        'edit_user_id'  => $edited ? rand(1, $users) : null,
                        'delete_time'     => $deleted ? $startTime = date_add($startTime, date_interval_create_from_date_string('1 second')) : null,
                        'delete_user_id'  => $deleted ? rand(1, $users) : null,
                    ]);

                    $posts[] = $post;
                }

                if (! $lastPost or $post->time >= $lastPost->time) {
                    $lastPost = $post;
                }

                if (rand(1, 20) == 1) {
                    $numberOffset += rand(0, 3);
                }
            }

            // Update the discussion's last post details.
            if ($lastPost) {
                $discussion->last_time        = $lastPost->time;
                $discussion->last_user_id     = $lastPost->user_id;
                $discussion->last_post_id     = $lastPost->id;
                $discussion->last_post_number = $lastPost->number;
                $discussion->number_index     = $lastPost->number;
            }

            $discussion->save();

            // Give some users some random discussion state data.
            for ($j = rand(0, 100); $j < 100; $j++) {
                try {
                    DiscussionState::create([
                        'discussion_id' => $discussion->id,
                        'user_id'       => rand(1, $users),
                        'read_number'   => rand(0, $posts_count - 1),
                        'read_time'     => $faker->dateTimeBetween($discussion->start_time, 'now')
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {

                }
            }
        }

        // Update user post and discussion counts.
        $prefix = DB::getTablePrefix();
        DB::table('users')->update([
            'discussions_count' => DB::raw('(SELECT COUNT(id) FROM '.$prefix.'discussions WHERE start_user_id = '.$prefix.'users.id)'),
            'posts_count' => DB::raw('(SELECT COUNT(id) FROM '.$prefix.'posts WHERE user_id = '.$prefix.'users.id and type = "comment")'),
        ]);
    }
}
