<?php

namespace Flarum\Frontend\Asset;

use Flarum\Frontend\Compiler\CompilerInterface;
use Flarum\Frontend\Compiler\Source\SourceCollector;

abstract class Type
{
    /** @var array|callable[]  */
    protected array $sources;
    protected string $compilerClass;
    protected ?CompilerInterface $compiler = null;
    protected string $filename;
    protected string $type;
    protected ?string $locale;

    public function __construct(string $filename, string $locale = null)
    {
        $this->filename = $filename;
        $this->locale = $locale;
    }

    public function addSource(callable $source)
    {
        $this->sources[] = $source;

        return $this;
    }

    public function getSources(): array
    {
        return $this->sources;
    }

    public function setSources(array $sources): static
    {
        $this->sources = $sources;

        return $this;
    }

    public function sources(): array
    {
        $collector = new SourceCollector;

        foreach ($this->sources as $source) {
            $source($collector);
        }

        return $collector->getSources();
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getCompilerClass(): string
    {
        return $this->compilerClass;
    }

    public function setCompiler(CompilerInterface $compiler): static
    {
        $compiler->addSources(function (SourceCollector $sources) {
            foreach ($this->sources as $callback) {
                $callback($sources, $this->locale);
            }
        });

        $this->compiler = $compiler;

        return $this;
    }

    public function getCompiler(): CompilerInterface
    {
        return $this->compiler;
    }
}
