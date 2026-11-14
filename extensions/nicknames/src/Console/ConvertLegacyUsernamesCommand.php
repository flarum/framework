<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Nicknames\Console;

use Flarum\Nicknames\RandomUsernameGenerator;
use Flarum\User\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Flarum 1.x gave members who signed up with "Randomize Usernames" an
 * all-digit username, which 2.x no longer accepts. This gives each of them a
 * username in the current format.
 *
 * Only members with a nickname are renamed: they are shown by it, so they
 * never see their username. A member with an all-digit username and no
 * nickname is shown by that username, and it may be what they log in with.
 */
class ConvertLegacyUsernamesCommand extends Command
{
    protected const CHUNK_SIZE = 1000;

    protected $signature = 'nicknames:convert-legacy-usernames
        {--dry-run : Count the members that would be renamed, without renaming them}
        {--force : Don\'t ask for confirmation}';

    protected $description = 'Give members who have a nickname and an all-digit username from Flarum 1.x a username in the current format';

    public function handle(RandomUsernameGenerator $generator, Dispatcher $events): int
    {
        [$count, $withoutNickname] = $this->count();

        if ($withoutNickname) {
            $this->line("$withoutNickname members have an all-digit username but no nickname. They are shown by their username, so they are left alone.");
        }

        if (! $count) {
            $this->info('No members have an all-digit username and a nickname. Nothing to do.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("$count members would be renamed.");

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("$count members will be renamed. Continue?")) {
            $this->warn('Aborting.');

            return self::FAILURE;
        }

        $progress = $this->output->createProgressBar($count);

        // Users are renamed one at a time, so the command can be stopped at any
        // point and run again to finish: those already renamed no longer match.
        $this->chunkLegacyUsers(function (Collection $ids) use ($generator, $events, $progress) {
            foreach (User::query()->whereIn('id', $ids)->get() as $user) {
                $user->rename($generator->generate());
                $user->save();

                foreach ($user->releaseEvents() as $event) {
                    $events->dispatch($event);
                }

                $progress->advance();
            }
        });

        $progress->finish();
        $this->newLine();

        $this->info("$count members renamed.");

        return self::SUCCESS;
    }

    /**
     * @return array{int, int} The members to rename, and those with an
     *                         all-digit username but no nickname.
     */
    protected function count(): array
    {
        $count = 0;
        $withoutNickname = 0;

        User::query()->select(['id', 'username', 'nickname'])->chunkById(self::CHUNK_SIZE, function (Collection $users) use (&$count, &$withoutNickname) {
            foreach ($users as $user) {
                if (! $this->isLegacy($user->username)) {
                    continue;
                }

                if ($this->hasNickname($user->nickname)) {
                    $count++;
                } else {
                    $withoutNickname++;
                }
            }
        });

        return [$count, $withoutNickname];
    }

    /**
     * Pass the IDs of the members to rename to the callback, a chunk at a time.
     *
     * The all-digit test is made here rather than in SQL, which has no
     * portable way to express it across the supported databases.
     */
    protected function chunkLegacyUsers(callable $callback): void
    {
        $this->withNickname()->select(['id', 'username'])->chunkById(self::CHUNK_SIZE, function (Collection $users) use ($callback) {
            $ids = $users->filter(fn (User $user) => $this->isLegacy($user->username))->pluck('id');

            if ($ids->isNotEmpty()) {
                $callback($ids);
            }
        });
    }

    /**
     * @return Builder<User>
     */
    protected function withNickname(): Builder
    {
        return User::query()->whereNotNull('nickname')->where('nickname', '<>', '');
    }

    protected function isLegacy(?string $username): bool
    {
        return $username !== null && ctype_digit($username);
    }

    protected function hasNickname(?string $nickname): bool
    {
        return $nickname !== null && $nickname !== '';
    }
}
