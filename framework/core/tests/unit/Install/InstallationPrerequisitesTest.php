<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Install;

use Flarum\Foundation\Paths;
use Flarum\Install\Installation;
use Flarum\Install\Prerequisite\Composite;
use Flarum\Install\Prerequisite\PhpExtensions;
use Flarum\Install\Prerequisite\PrerequisiteInterface;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;

class InstallationPrerequisitesTest extends TestCase
{
    #[Test]
    public function it_requires_the_curl_extension(): void
    {
        $installation = new Installation(new Paths([
            'base' => __DIR__,
            'public' => __DIR__,
            'storage' => __DIR__,
        ]));

        $this->assertContains('curl', $this->requiredExtensions($installation->prerequisites()));
    }

    private function requiredExtensions(PrerequisiteInterface $composite): array
    {
        $this->assertInstanceOf(Composite::class, $composite);

        $prerequisites = (new ReflectionProperty(Composite::class, 'prerequisites'))->getValue($composite);

        foreach ($prerequisites as $prerequisite) {
            if ($prerequisite instanceof PhpExtensions) {
                return (new ReflectionProperty(PhpExtensions::class, 'extensions'))->getValue($prerequisite);
            }
        }

        $this->fail('The installer checks no required PHP extensions.');
    }
}
