<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\unit;

use Flarum\Api\Resource\PostResource;
use Flarum\Extension\Extension;
use Flarum\Extension\ExtensionManager;
use Flarum\Mcp\Overview\ExtensionOwnership;
use Flarum\Mcp\Tests\unit\Fixtures\ReplacedPostResource;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ExtensionOwnershipTest extends TestCase
{
    protected function ownership(): ExtensionOwnership
    {
        $extension = $this->createStub(Extension::class);
        $extension->method('composerJsonAttribute')->willReturn(['Flarum\\Mcp\\Tests\\' => 'tests/']);

        $extensions = $this->createStub(ExtensionManager::class);
        $extensions->method('getEnabledExtensions')->willReturn(['acme-replacer' => $extension]);

        return new ExtensionOwnership($extensions);
    }

    #[Test]
    public function a_core_resource_an_extension_replaces_still_belongs_to_core(): void
    {
        $this->assertSame(ExtensionOwnership::CORE, $this->ownership()->owner(PostResource::class));
        $this->assertSame(ExtensionOwnership::CORE, $this->ownership()->owner(ReplacedPostResource::class));
    }

    #[Test]
    public function a_class_an_extension_defines_belongs_to_it(): void
    {
        // Its parent, PHPUnit's TestCase, is abstract: nothing is being replaced.
        $this->assertSame('acme-replacer', $this->ownership()->owner(self::class));
    }
}
