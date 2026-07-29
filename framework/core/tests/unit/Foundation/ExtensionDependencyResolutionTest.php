<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Foundation;

use Flarum\Extension\ExtensionManager;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ExtensionDependencyResolutionTest extends TestCase
{
    public $tags;
    public $categories;
    public $tagBackgrounds;
    public $something;
    public $help;
    public $missing;
    public $circular1;
    public $circular2;
    public $optionalDependencyCategories;

    public function setUp(): void
    {
        parent::setUp();

        $this->tags = new FakeExtension('flarum-tags', []);
        $this->categories = new FakeExtension('flarum-categories', ['flarum-tags', 'flarum-tag-backgrounds']);
        $this->tagBackgrounds = new FakeExtension('flarum-tag-backgrounds', ['flarum-tags']);
        $this->something = new FakeExtension('flarum-something', ['flarum-categories', 'flarum-help']);
        $this->help = new FakeExtension('flarum-help', []);
        $this->missing = new FakeExtension('flarum-missing', ['this-does-not-exist', 'flarum-tags', 'also-not-exists']);
        $this->circular1 = new FakeExtension('circular1', ['circular2']);
        $this->circular2 = new FakeExtension('circular2', ['circular1']);
        $this->optionalDependencyCategories = new FakeExtension('flarum-categories', ['flarum-tags'], ['flarum-tag-backgrounds', 'non-existent-optional-dependency']);
    }

    #[Test]
    public function works_with_empty_set()
    {
        $expected = [
            'valid' => [],
            'missingDependencies' => [],
            'circularDependencies' => [],
        ];

        $this->assertEquals($expected, ExtensionManager::resolveExtensionOrder([]));
    }

    #[Test]
    public function works_with_proper_data()
    {
        $exts = [$this->tags, $this->categories, $this->tagBackgrounds, $this->something, $this->help];

        $expected = [
            'valid' => [$this->tags, $this->tagBackgrounds, $this->help, $this->categories, $this->something],
            'missingDependencies' => [],
            'circularDependencies' => [],
        ];

        $this->assertEquals($expected, ExtensionManager::resolveExtensionOrder($exts));
    }

    #[Test]
    public function works_with_missing_dependencies()
    {
        $exts = [$this->tags, $this->categories, $this->tagBackgrounds, $this->something, $this->help, $this->missing];

        $expected = [
            'valid' => [$this->tags, $this->tagBackgrounds, $this->help, $this->categories, $this->something],
            'missingDependencies' => ['flarum-missing' => ['this-does-not-exist', 'also-not-exists']],
            'circularDependencies' => [],
        ];

        $this->assertEquals($expected, ExtensionManager::resolveExtensionOrder($exts));
    }

    #[Test]
    public function works_with_circular_dependencies()
    {
        $exts = [$this->tags, $this->categories, $this->tagBackgrounds, $this->something, $this->help, $this->circular1, $this->circular2];

        $expected = [
            'valid' => [$this->tags, $this->tagBackgrounds, $this->help, $this->categories, $this->something],
            'missingDependencies' => [],
            'circularDependencies' => ['circular2', 'circular1'],
        ];

        $this->assertEquals($expected, ExtensionManager::resolveExtensionOrder($exts));
    }

    #[Test]
    public function works_with_optional_dependencies()
    {
        $exts = [$this->tags, $this->optionalDependencyCategories, $this->tagBackgrounds, $this->something, $this->help];

        $expected = [
            'valid' => [$this->tags, $this->tagBackgrounds, $this->help, $this->optionalDependencyCategories, $this->something],
            'missingDependencies' => [],
            'circularDependencies' => [],
        ];

        $this->assertEquals($expected, ExtensionManager::resolveExtensionOrder($exts));
    }

    #[Test]
    public function works_with_optional_dependencies_if_optional_dependency_missing()
    {
        $exts = [$this->tags, $this->optionalDependencyCategories, $this->something, $this->help];

        $expected = [
            'valid' => [$this->tags, $this->help, $this->optionalDependencyCategories, $this->something],
            'missingDependencies' => [],
            'circularDependencies' => [],
        ];

        $this->assertEquals($expected, ExtensionManager::resolveExtensionOrder($exts));
    }

    /**
     * Regression test for https://discuss.flarum.org/d/39359.
     *
     * flarum/realtime registers its `Realtime` JS extender on the export
     * registry at module-evaluation time, and consumer extensions read it
     * back via `flarum.reg.get(...)`. The combined forum.js concatenates
     * extensions in resolved order, so realtime MUST be ordered before any
     * consumer — otherwise the consumer's `reg.get` runs before realtime's
     * `reg.add` and instantiates `undefined` ("mt(...) is not a constructor").
     *
     * With no dependency edge, resolveExtensionOrder falls back to
     * REVERSE-alphabetical by id (Kahn's algorithm output is reversed at the
     * end). So a consumer whose id sorts AFTER "flarum-realtime" — e.g.
     * "flarum-tags" or "flarum-sticky" — is emitted BEFORE realtime: the
     * broken order. (Consumers with ids before "flarum-realtime", like
     * flags/likes/lock/messages, happen to come out after realtime and work by
     * luck.) Declaring flarum/realtime as an optional dependency forces
     * realtime ahead of the consumer regardless of id.
     */
    #[Test]
    public function tags_without_realtime_dependency_falls_back_to_broken_order()
    {
        $realtime = new FakeExtension('flarum-realtime', []);
        $tags = new FakeExtension('flarum-tags', []); // no optional dep — the bug

        $resolved = ExtensionManager::resolveExtensionOrder([$tags, $realtime]);
        $order = array_map(fn ($e) => $e->getId(), $resolved['valid']);

        // Broken: "flarum-tags" sorts after "flarum-realtime", and reverse
        // ordering therefore emits tags BEFORE realtime.
        $this->assertSame(['flarum-tags', 'flarum-realtime'], $order);
    }

    #[Test]
    public function tags_with_realtime_optional_dependency_is_ordered_after_realtime()
    {
        $realtime = new FakeExtension('flarum-realtime', []);
        // The fix: declare flarum/realtime as an optional dependency.
        $tags = new FakeExtension('flarum-tags', [], ['flarum-realtime']);

        $resolved = ExtensionManager::resolveExtensionOrder([$tags, $realtime]);
        $order = array_map(fn ($e) => $e->getId(), $resolved['valid']);

        $this->assertEmpty($resolved['missingDependencies']);
        $this->assertEmpty($resolved['circularDependencies']);
        $this->assertSame(['flarum-realtime', 'flarum-tags'], $order);
        $this->assertLessThan(
            array_search('flarum-tags', $order),
            array_search('flarum-realtime', $order),
            'flarum-realtime must be ordered before a consumer that optionally depends on it'
        );
    }
}

class FakeExtension
{
    protected $id;
    protected $extensionDependencies;
    protected $optionalDependencies;

    public function __construct($id, $extensionDependencies, $optionalDependencies = [])
    {
        $this->id = $id;
        $this->extensionDependencies = $extensionDependencies;
        $this->optionalDependencies = $optionalDependencies;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getExtensionDependencyIds()
    {
        return $this->extensionDependencies;
    }

    public function getOptionalDependencyIds()
    {
        return $this->optionalDependencies;
    }
}
