<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Overview;

use Flarum\Extension\ExtensionManager;

/**
 * Which enabled extension a class belongs to, from the PSR-4 namespaces in
 * each extension's composer.json. Anything else belongs to `core`.
 */
class ExtensionOwnership
{
    public const string CORE = 'core';

    /**
     * @var array<string, string>|null
     */
    protected ?array $namespaces = null;

    public function __construct(
        protected ExtensionManager $extensions,
    ) {
    }

    public function owner(object|string $class): string
    {
        $class = ltrim(is_object($class) ? $class::class : $class, '\\');

        // An extension may replace a class with a subclass of its own, bound in
        // the container; what it replaces still belongs to whoever defined it.
        while (($parent = get_parent_class($class)) && ! (new \ReflectionClass($parent))->isAbstract()) {
            $class = $parent;
        }

        $owner = static::CORE;
        $longest = 0;

        foreach ($this->namespaces() as $namespace => $extensionId) {
            if (str_starts_with($class, $namespace) && strlen($namespace) > $longest) {
                $owner = $extensionId;
                $longest = strlen($namespace);
            }
        }

        return $owner;
    }

    /**
     * @return array<string, string> namespace prefix => extension ID
     */
    protected function namespaces(): array
    {
        return $this->namespaces ??= $this->buildNamespaces();
    }

    /**
     * @return array<string, string>
     */
    protected function buildNamespaces(): array
    {
        $namespaces = [];

        foreach ($this->extensions->getEnabledExtensions() as $id => $extension) {
            foreach (array_keys((array) $extension->composerJsonAttribute('autoload.psr-4')) as $namespace) {
                $namespace = trim((string) $namespace, '\\');

                if ($namespace !== '') {
                    $namespaces[$namespace.'\\'] = (string) $id;
                }
            }
        }

        return $namespaces;
    }
}
