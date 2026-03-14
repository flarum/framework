<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Mail;

use Flarum\Mail\NullDriver;
use Flarum\Testing\unit\TestCase;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mailer\Transport\NullTransport;

class NullDriverTest extends TestCase
{
    private NullDriver $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->driver = new NullDriver();
    }

    #[Test]
    public function has_no_available_settings(): void
    {
        $this->assertEmpty($this->driver->availableSettings());
    }

    #[Test]
    public function cannot_send(): void
    {
        $this->assertFalse($this->driver->canSend());
    }

    #[Test]
    public function validate_returns_empty_message_bag(): void
    {
        $settings = m::mock(\Flarum\Settings\SettingsRepositoryInterface::class);
        $validator = m::mock(\Illuminate\Contracts\Validation\Factory::class);

        $errors = $this->driver->validate($settings, $validator);

        $this->assertEmpty($errors->all());
    }

    #[Test]
    public function build_transport_returns_null_transport(): void
    {
        $settings = m::mock(\Flarum\Settings\SettingsRepositoryInterface::class);

        $transport = $this->driver->buildTransport($settings);

        $this->assertInstanceOf(NullTransport::class, $transport);
    }
}
