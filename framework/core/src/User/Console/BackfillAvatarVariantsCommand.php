<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Console;

use Flarum\Console\AbstractCommand;
use Flarum\User\User;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputOption;

/**
 * Populate the `has_avatar_2x` / `has_avatar_3x` columns for users whose
 * avatars predate variant tracking (uploaded before the columns existed, or
 * uploaded by a future code path that didn't set them).
 *
 * The columns default to `false`, which is *correct* in that the srcset will
 * fall back to the 1× URL — but it loses HiDPI rendering for avatars that do
 * have variant files on disk. Operators can run this command once after upgrade
 * to restore HiDPI for those users. On remote-storage installs the per-row cost
 * is two `exists()` round-trips, so chunked execution is recommended.
 */
class BackfillAvatarVariantsCommand extends AbstractCommand
{
    private Filesystem $uploadDir;

    public function __construct(Factory $filesystemFactory)
    {
        $this->uploadDir = $filesystemFactory->disk('flarum-avatars');
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('avatars:backfill-variants')
            ->setDescription('Detect and record which HiDPI variants (@2x, @3x) exist for each locally-stored avatar.')
            ->addOption(
                'chunk',
                null,
                InputOption::VALUE_REQUIRED,
                'Number of users to process per database chunk',
                '100'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Re-check users even if both flags are already set'
            );
    }

    protected function fire(): int
    {
        $chunkSize = max(1, (int) $this->input->getOption('chunk'));
        $force = (bool) $this->input->getOption('force');

        $query = User::whereNotNull('avatar_url')
            ->where('avatar_url', 'not like', '%://%');

        if (! $force) {
            $query->where(function ($q) {
                $q->where('has_avatar_2x', false)->orWhere('has_avatar_3x', false);
            });
        }

        $total = $query->count();

        if ($total === 0) {
            $this->info('No avatars to check.');

            return Command::SUCCESS;
        }

        $this->info("Checking variant files for $total avatar(s)...");

        $progress = new ProgressBar($this->output, $total);
        $progress->start();

        $updated = 0;
        $unchanged = 0;
        $missing = 0;

        $query->chunkById($chunkSize, function ($users) use ($progress, &$updated, &$unchanged, &$missing) {
            foreach ($users as $user) {
                $basePath = $user->getRawOriginal('avatar_url');

                // The base file must exist for variants to be meaningful — a user
                // whose 1× is missing is broken regardless of variant flags.
                if (! $this->uploadDir->exists($basePath)) {
                    $missing++;
                    $progress->advance();
                    continue;
                }

                $has2x = $this->uploadDir->exists($this->variantPath($basePath, '@2x'));
                $has3x = $this->uploadDir->exists($this->variantPath($basePath, '@3x'));

                if ($user->has_avatar_2x === $has2x && $user->has_avatar_3x === $has3x) {
                    $unchanged++;
                } else {
                    User::where('id', $user->id)->update([
                        'has_avatar_2x' => $has2x,
                        'has_avatar_3x' => $has3x,
                    ]);
                    $updated++;
                }

                $progress->advance();
            }
        });

        $progress->finish();
        $this->output->writeln('');
        $this->info("Done. Updated: $updated, Unchanged: $unchanged, Base file missing: $missing.");

        return Command::SUCCESS;
    }

    private function variantPath(string $basePath, string $suffix): string
    {
        $dot = strrpos($basePath, '.');

        return $dot !== false
            ? substr($basePath, 0, $dot).$suffix.substr($basePath, $dot)
            : $basePath.$suffix;
    }
}
