<?php

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
 * @method DirectorySource[] getSources()
 */
class JsDirectoryCompiler implements CompilerInterface
{
    use HasSources;

    public function __construct(
        protected Cloud $assetsDir,
        protected string $destinationPath
    ) {
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
        foreach ($this->getSources() as $source) {
            $this->compileSource($source, $force);
        }
    }

    public function getUrl(): ?string
    {
        return null;
    }

    public function flush(): void
    {
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
    }

    protected function eachFile(DirectorySource $source, callable $callback): void
    {
        $filesystem = $source->getFilesystem();

        foreach ($filesystem->allFiles() as $relativeFilePath) {
            // Skip non-JS files.
            if ($filesystem->mimeType($relativeFilePath) !== 'application/javascript') {
                continue;
            }

            $jsCompiler = $this->compilerFor($source, $filesystem, $relativeFilePath);
            $callback($jsCompiler);
        }
    }

    protected function compilerFor(DirectorySource $source, FilesystemAdapter $filesystem, mixed $relativeFilePath): JsCompiler
    {
        // Filesystem's root is the actual directory we want to copy.
        // The destination path is relative to the assets' filesystem.

        $extensionId = $source->getExtensionId() ?? 'core';

        $jsCompiler = resolve(JsCompiler::class, [
            'assetsDir' => $this->assetsDir,
            // We put each file in `js/extensionId/frontend` (path provided) `/relativeFilePath` (such as `components/LogInModal.js`).
            'filename' => str_replace('{ext}', $extensionId, $this->destinationPath).DIRECTORY_SEPARATOR.$relativeFilePath,
        ]);

        $jsCompiler->addSources(
            fn (SourceCollector $sources) => $sources->addFile($filesystem->path($relativeFilePath), $source->getExtensionId())
        );

        return $jsCompiler;
    }
}
