<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Tests\unit;

use Flarum\Ai\Drivers;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DriversTest extends TestCase
{
    #[Test]
    public function bedrock_is_offered_only_with_the_aws_sdk(): void
    {
        $this->assertContains('bedrock', Drivers::admin(true));
        $this->assertNotContains('bedrock', Drivers::admin(false));
        $this->assertContains('anthropic', Drivers::admin(false));
    }

    #[Test]
    public function a_bedrock_connection_stays_editable_without_the_aws_sdk(): void
    {
        $this->assertTrue(Drivers::allowed('bedrock', 'bedrock', false));
        $this->assertFalse(Drivers::allowed('bedrock', 'anthropic', false));
        $this->assertFalse(Drivers::allowed('bedrock', null, false));
        $this->assertTrue(Drivers::allowed('bedrock', null, true));
        $this->assertTrue(Drivers::allowed('anthropic', null, false));
        $this->assertFalse(Drivers::allowed('azure', 'azure', true));
    }

    #[Test]
    public function bedrock_needs_a_key_unless_the_server_lends_its_credentials(): void
    {
        $this->assertContains('ollama', Drivers::keyOptional(false));
        $this->assertNotContains('bedrock', Drivers::keyOptional(false));
        $this->assertContains('bedrock', Drivers::keyOptional(true));
    }
}
