<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Compiler\Source;

use Closure;

/**
 * @internal
 */
class SourceCollector
{
    public function __construct(
        protected array $allowedSourceTypes = []
    ) {
    }

    /**
     * @var SourceInterface[]
     */
    protected array $sources = [];

    public function addFile(string $file, ?string $extensionId = null): static
    {
        $this->sources[] = $this->validateSourceType(
            new FileSource($file, $extensionId)
        );

        return $this;
    }

    public function addString(Closure $callback, ?string $key = null): static
    {
        $source = $this->validateSourceType(
            new StringSource($callback)
        );

        if (! empty($key)) {
            $this->sources[$key] = $source;
        } else {
            $this->sources[] = $source;
        }

        return $this;
    }

    public function addDirectory(string $directory, ?string $extensionId = null): static
    {
        $this->sources[] = $this->validateSourceType(
            new DirectorySource($directory, $extensionId)
        );

        return $this;
    }

    /**
     * @return SourceInterface[]
     */
    public function getSources(): array
    {
        return $this->sources;
    }

    protected function validateSourceType(SourceInterface $source): SourceInterface
    {
        // allowedSourceTypes is an array of class names (or interface names)
        // so we need to check if the $source is an instance of one of those classes/interfaces (could be a parent class as well)
        $isInstanceOfOneOfTheAllowedSourceTypes = false;

        foreach ($this->allowedSourceTypes as $allowedSourceType) {
            if ($source instanceof $allowedSourceType) {
                $isInstanceOfOneOfTheAllowedSourceTypes = true;
                break;
            }
        }

        if (! empty($this->allowedSourceTypes) && ! $isInstanceOfOneOfTheAllowedSourceTypes) {
            throw new \InvalidArgumentException(sprintf(
                'Source type %s is not allowed for this collector. Allowed types are: %s',
                $source::class,
                implode(', ', $this->allowedSourceTypes)
            ));
        }

        return $source;
    }
}
