<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Foundation;

use Flarum\Extension\ExtensionManager;
use Flarum\Tests\unit\TestCase;

class ExtensionDependencyResolutionTest extends TestCase
{
    public function setUp()
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
    }

    /** @test */
    public function works_with_empty_set()
    {
        $expected = [
            'valid' => [],
            'missingDependencies' => [],
            'circularDependencies' => [],
        ];

        $this->assertEquals($expected, ExtensionManager::resolveExtensionOrder([]));
    }

    /** @test */
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

    /** @test */
    public function works_with_proper_data_in_different_order()
    {
        $exts = [$this->help, $this->categories, $this->tagBackgrounds, $this->tags, $this->something];

        $expected = [
            'valid' => [$this->tags, $this->tagBackgrounds, $this->help, $this->categories, $this->something],
            'missingDependencies' => [],
            'circularDependencies' => [],
        ];

        $this->assertEquals($expected, ExtensionManager::resolveExtensionOrder($exts));
    }

    /** @test */
    public function works_with_proper_data_in_yet_another_order()
    {
        $exts = [$this->something, $this->tagBackgrounds, $this->help, $this->categories, $this->tags];

        $expected = [
            'valid' => [$this->tags, $this->tagBackgrounds, $this->help, $this->categories, $this->something],
            'missingDependencies' => [],
            'circularDependencies' => [],
        ];

        $this->assertEquals($expected, ExtensionManager::resolveExtensionOrder($exts));
    }

    /** @test */
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

    /** @test */
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
}

class FakeExtension
{
    protected $id;
    public $extensionDependencies;

    public function __construct($id, $extensionDependencies)
    {
        $this->id = $id;
        $this->extensionDependencies = $extensionDependencies;
    }

    public function getId()
    {
        return $this->id;
    }
}
