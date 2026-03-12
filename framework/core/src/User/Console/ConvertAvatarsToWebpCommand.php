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
use Intervention\Image\ImageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Throwable;

class ConvertAvatarsToWebpCommand extends AbstractCommand
{
    private Filesystem $uploadDir;

    public function __construct(
        private readonly ImageManager $imageManager,
        Factory $filesystemFactory,
    ) {
        $this->uploadDir = $filesystemFactory->disk('flarum-avatars');
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('avatars:convert-to-webp')
            ->setDescription('Convert existing local user avatars from PNG/JPEG/BMP to WebP format');
    }

    protected function fire(): int
    {
        $query = User::whereNotNull('avatar_url')
            ->where('avatar_url', 'not like', '%://%')
            ->where('avatar_url', 'not like', '%.webp')
            ->where('avatar_url', 'not like', '%.gif');

        $total = $query->count();

        if ($total === 0) {
            $this->info('No avatars to convert.');

            return Command::SUCCESS;
        }

        $this->info("Converting $total avatar(s) to WebP...");

        $progress = new ProgressBar($this->output, $total);
        $progress->start();

        $converted = 0;
        $failed = 0;
        $missing = 0;

        $query->chunkById(100, function ($users) use ($progress, &$converted, &$failed, &$missing) {
            foreach ($users as $user) {
                $oldPath = $user->getRawOriginal('avatar_url');

                if (! $this->uploadDir->exists($oldPath)) {
                    $missing++;
                    $progress->advance();
                    continue;
                }

                try {
                    $contents = $this->uploadDir->get($oldPath);
                    $webpContents = $this->imageManager->read($contents)->toWebp();

                    $newPath = pathinfo($oldPath, PATHINFO_FILENAME).'.webp';

                    $this->uploadDir->put($newPath, $webpContents);

                    User::where('id', $user->id)->update(['avatar_url' => $newPath]);

                    $this->uploadDir->delete($oldPath);

                    $converted++;
                } catch (Throwable) {
                    $failed++;
                }

                $progress->advance();
            }
        });

        $progress->finish();
        $this->output->writeln('');
        $this->info("Done. Converted: $converted, Missing: $missing, Failed: $failed.");

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
