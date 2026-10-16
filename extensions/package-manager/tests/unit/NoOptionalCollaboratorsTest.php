<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;

/**
 * No service may accept an object collaborator it can do without.
 *
 * 🚨 This rule exists because breaking it silently removed a safety feature
 * from every install for the whole life of that feature.
 *
 * `ComposerStepsFactory::__construct` took `?Config $config = null`. The one
 * binding that builds it passed only `Paths`. Nothing errored, nothing warned:
 * the site URL came back as an empty string, so the post-update health check
 * and the automatic rollback-when-the-site-breaks never ran anywhere. The unit
 * test for the rollback RULE passed the entire time, because the rule was
 * correct — it was simply never reached.
 *
 * An optional object dependency is indistinguishable from an unneeded one. A
 * required one turns the same mistake into a TypeError at construction, where
 * somebody sees it.
 *
 * Scalars and callables may have defaults — a timeout or a clock is a genuine
 * option. A collaborator is not.
 */
class NoOptionalCollaboratorsTest extends TestCase
{
    public function test_no_constructor_takes_an_optional_object(): void
    {
        $offenders = [];

        foreach ($this->classes() as $class) {
            $constructor = (new ReflectionClass($class))->getConstructor();

            if ($constructor === null) {
                continue;
            }

            foreach ($constructor->getParameters() as $parameter) {
                $type = $parameter->getType();

                if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                    continue;
                }

                // A closure is a strategy, not a collaborator to be wired.
                if (in_array($type->getName(), ['Closure', 'callable'], true)) {
                    continue;
                }

                if ($parameter->isDefaultValueAvailable() || $type->allowsNull()) {
                    $offenders[] = $class.'::__construct($'.$parameter->getName().' : '
                        .($type->allowsNull() ? '?' : '').$type->getName().')';
                }
            }
        }

        $this->assertSame([], $offenders, implode("\n", array_merge(
            ['An object dependency that can be omitted WILL be omitted, and nothing will say so:'],
            $offenders,
            ['', 'Make it required. If it is genuinely optional, it is a strategy — take an interface with a null-object implementation instead of a nullable one.']
        )));
    }

    /** @return list<class-string> */
    private function classes(): array
    {
        $root = dirname(__DIR__, 2).'/src';
        $found = [];

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($root) + 1, -4);
            $class = 'Flarum\\ExtensionManager\\'.str_replace('/', '\\', $relative);

            if (class_exists($class)) {
                $found[] = $class;
            }
        }

        sort($found);

        return $found;
    }
}
