<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Console;

use Composer\Autoload\ClassLoader;
use Flarum\Database\AbstractModel;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Lets tinker users reference Eloquent models by their short name — e.g.
 * `User::find(1)` instead of `Flarum\User\User::find(1)`.
 *
 * Registered as a fallback autoloader, so it only runs when PHP fails to
 * resolve a class by its given (short) name. On the first miss it indexes every
 * registered PSR-4 root (core, extensions, and other Composer packages) by
 * short name; when a bare name is requested it aliases the matching Eloquent
 * model into the global namespace. This means third-party extension models are
 * covered automatically, with no hardcoded list. The index is built once and
 * cached for the lifetime of the shell.
 */
class ModelAliasAutoloader
{
    /**
     * Short name => list of fully-qualified model class names.
     *
     * @var array<string, list<class-string>>|null
     */
    protected ?array $index = null;

    /**
     * Short names we have already aliased, so we don't do the work twice.
     *
     * @var array<string, true>
     */
    protected array $aliased = [];

    public function __construct(
        protected ClassLoader $loader,
        protected \Closure $writeln
    ) {
    }

    public static function register(\Closure $writeln): ?self
    {
        $loader = static::findComposerLoader();

        if ($loader === null) {
            return null;
        }

        $instance = new static($loader, $writeln);

        // Append (prepend = false) so this only fires after every real
        // autoloader has failed to find the (short) class name.
        spl_autoload_register([$instance, 'load'], true, false);

        return $instance;
    }

    /**
     * Remove this autoloader. Safe to call more than once.
     */
    public function unregister(): void
    {
        spl_autoload_unregister([$this, 'load']);
    }

    /**
     * Laravel facades that don't exist in Flarum, mapped to the hint we show
     * when someone reaches for them out of habit. Flarum doesn't register
     * Laravel's facades, so these resolve to nothing without a nudge.
     *
     * @var array<string, string>
     */
    protected array $facadeHints = [
        'DB' => 'Flarum does not register Laravel facades. Use the <comment>$db</comment> variable instead, e.g. <comment>$db->table(\'users\')->count()</comment>.',
        'Cache' => 'Flarum does not register Laravel facades. Resolve the cache with <comment>resolve(Illuminate\Contracts\Cache\Store::class)</comment>.',
        'Event' => 'Flarum does not register Laravel facades. Use the <comment>$events</comment> variable instead.',
        'Schema' => 'Flarum does not register Laravel facades. Use <comment>$db->getSchemaBuilder()</comment> instead.',
        'Queue' => 'Flarum does not register Laravel facades. Resolve the queue with <comment>resolve(Illuminate\Contracts\Queue\Queue::class)</comment>.',
    ];

    public function load(string $class): void
    {
        // Only bare, unqualified names are candidates for aliasing.
        if (str_contains($class, '\\') || isset($this->aliased[$class])) {
            return;
        }

        // Candidate FQNs that share this short name (derived from filenames,
        // no classes loaded yet). Only these get the expensive model check.
        $candidates = $this->buildIndex()[$class] ?? [];

        $models = array_values(array_filter($candidates, function (string $fqn) {
            return class_exists($fqn) && is_subclass_of($fqn, AbstractModel::class);
        }));

        if (count($models) === 1) {
            // Guard against re-aliasing (class_alias warns and returns false if
            // the name already exists), e.g. when the command runs more than
            // once in one process, or a real global class of this name exists.
            if (! class_exists($class, false)) {
                class_alias($models[0], $class);
            }

            $this->aliased[$class] = true;

            return;
        }

        if (count($models) > 1) {
            ($this->writeln)(sprintf(
                "<comment>%s</comment> is ambiguous. Use the full name:\n  %s",
                $class,
                implode("\n  ", $models)
            ));

            return;
        }

        // No model claimed this name — if it's a Laravel facade someone reached
        // for out of habit, nudge them towards the Flarum equivalent.
        if (isset($this->facadeHints[$class])) {
            ($this->writeln)("<comment>$class</comment>: {$this->facadeHints[$class]}");
        }
    }

    /**
     * @return array<string, list<class-string>>
     */
    protected function buildIndex(): array
    {
        if ($this->index !== null) {
            return $this->index;
        }

        $this->index = [];

        foreach ($this->loader->getPrefixesPsr4() as $prefix => $dirs) {
            foreach ($dirs as $dir) {
                if (! is_dir($dir)) {
                    continue;
                }

                $this->scanDirectory($prefix, $dir);
            }
        }

        return $this->index;
    }

    protected function scanDirectory(string $prefix, string $dir): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($dir) + 1);
            $class = $prefix.str_replace(['/', '.php'], ['\\', ''], $relative);

            $short = strrchr($class, '\\');
            $short = $short === false ? $class : substr($short, 1);

            // Index by filename only — no classes are loaded here. The actual
            // "is this an Eloquent model?" check happens in load(), and only
            // for the few candidates that share the requested short name.
            $this->index[$short][] = $class;
        }
    }

    protected static function findComposerLoader(): ?ClassLoader
    {
        foreach (spl_autoload_functions() as $function) {
            if (is_array($function) && $function[0] instanceof ClassLoader) {
                return $function[0];
            }
        }

        return null;
    }
}
