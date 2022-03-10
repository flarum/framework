<?php

namespace Flarum\Testing\integration;

trait UsesTmpDir
{
    public function tmpDir() {
        return getenv('FLARUM_TEST_TMP_DIR_LOCAL') ?: getenv('FLARUM_TEST_TMP_DIR') ?: __DIR__.'/tmp';
    }

    public function setupTmpDir() {
        $DIRS_NEEDED = [
            '/',
            '/public',
            '/public/assets',
            '/storage',
            '/storage/formatter',
            '/storage/sessions',
            '/storage/views',
            '/vendor',
            '/vendor/composer'
        ];

        $FILES_NEEDED = [
            '/vendor/composer/installed.json' => '{}'
        ];

        $tmpDir = $this->tmpDir();

        foreach ($DIRS_NEEDED as $path) {
            $fullPath = $tmpDir.$path;
            if (!file_exists($fullPath)) {
                mkdir($fullPath);
            }
        }

        foreach ($FILES_NEEDED as $path => $contents) {
            $fullPath = $tmpDir.$path;
            if (!file_exists($fullPath)) {
                file_put_contents($fullPath, $contents);
            }
        }
    }
}
