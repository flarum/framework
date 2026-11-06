<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Tests\unit;

use Flarum\Ai\Drivers;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DriversTest extends TestCase
{
    #[Test]
    public function bedrock_is_offered_only_with_the_aws_sdk(): void
    {
        $this->assertContains('bedrock', Drivers::admin(true));
        $this->assertNotContains('bedrock', Drivers::admin(false));
        $this->assertContains('anthropic', Drivers::admin(false));
    }
}
