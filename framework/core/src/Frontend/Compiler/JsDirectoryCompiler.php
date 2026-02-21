<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Compiler;

use Flarum\Frontend\Compiler\Concerns\HasSources;
use Flarum\Frontend\Compiler\Source\DirectorySource;
use Flarum\Frontend\Compiler\Source\SourceCollector;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Filesystem\FilesystemAdapter;

/**
 * Used to copy JS files from a package directory to the assets' directory.
 * Without concatenating them. Primarily used for lazy loading JS modules.
 *
 * @uses HasSources<DirectorySource>
 */
class JsDirectoryCompiler implements CompilerInterface
{
    use HasSources;

    protected VersionerInterface $versioner;

    public function __construct(
        protected Cloud $assetsDir,
        protected string $destinationPath
    ) {
        $this->versioner = new FileVersioner($assetsDir);
    }

    public function getFilename(): ?string
    {
        return null;
    }

    public function setFilename(string $filename): void
    {
        //
    }

    public function commit(bool $force = false): void
    {
        /** @var DirectorySource $source */
        foreach ($this->getSources() as $source) {
            $this->compileSource($source, $force);
        }
    }

    public function getUrl(): ?string
    {
        /** @var DirectorySource $source */
        foreach ($this->getSources() as $source) {
            $this->eachFile($source, fn (JsCompiler $compiler) => $compiler->getUrl());
        }

        return null;
    }

    public function flush(): void
    {
        /** @var DirectorySource $source */
        foreach ($this->getSources() as $source) {
            $this->flushSource($source);
        }

        // Delete the remaining empty directory.
        $this->assetsDir->deleteDirectory($this->destinationPath);
    }

    protected function allowedSourceTypes(): array
    {
        return [DirectorySource::class];
    }

    protected function compileSource(DirectorySource $source, bool $force = false): void
    {
        $this->eachFile($source, fn (JsCompiler $compiler) => $compiler->commit($force));
    }

    protected function flushSource(DirectorySource $source): void
    {
        $this->eachFile($source, fn (JsCompiler $compiler) => $compiler->flush());

        $destinationDir = $this->destinationFor($source);

        // Destination can still contain stale chunks.
        $this->assetsDir->deleteDirectory($destinationDir);

        // Delete stale revisions.
        $remainingRevisions = $this->versioner->allRevisions();

        foreach ($remainingRevisions as $filename => $revision) {
            if (str_starts_with($filename, $destinationDir)) {
                $this->versioner->putRevision($filename, null);
            }
        }
    }

    protected function eachFile(DirectorySource $source, callable $callback): void
    {
        $filesystem = $source->getFilesystem();

        foreach ($filesystem->allFiles() as $relativeFilePath) {
            // Check both file extension and MIME type to identify JS files.
            // We require at least one of these checks to pass, as MIME type detection
            // can be unreliable for minified/bundled JS files.
            $hasJsExtension = str_ends_with($relativeFilePath, '.js');
            $mimeType = $filesystem->mimeType($relativeFilePath);
            $hasJsMimeType = $mimeType === 'application/javascript' || $mimeType === 'text/javascript';

            if (! $hasJsExtension && ! $hasJsMimeType) {
                continue;
            }

            $jsCompiler = $this->compilerFor($source, $filesystem, $relativeFilePath);
            $callback($jsCompiler);
        }
    }

    protected function compilerFor(DirectorySource $source, FilesystemAdapter $filesystem, string $relativeFilePath): JsCompiler
    {
        // Filesystem's root is the actual directory we want to copy.
        // The destination path is relative to the assets' filesystem.

        $jsCompiler = resolve(JsCompiler::class, [
            'assetsDir' => $this->assetsDir,
            // We put each file in `js/extensionId/frontend` (path provided) `/relativeFilePath` (such as `components/LogInModal.js`).
            'filename' => $this->destinationFor($source, $relativeFilePath),
        ]);

        $jsCompiler->addSources(
            fn (SourceCollector $sources) => $sources->addFile($filesystem->path($relativeFilePath), $source->getExtensionId())
        );

        return $jsCompiler;
    }

    protected function destinationFor(DirectorySource $source, ?string $relativeFilePath = null): string
    {
        $extensionId = $source->getExtensionId() ?? 'core';

        return str_replace('{ext}', $extensionId, $this->destinationPath).DIRECTORY_SEPARATOR.$relativeFilePath;
    }
}
