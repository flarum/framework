<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Mail;

use Flarum\Mail\SmtpDriver;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\unit\TestCase;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mailer\Transport\TransportFactoryInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;

class SmtpDriverTest extends TestCase
{
    private ?Dsn $lastDsn = null;
    private SmtpDriver $driver;

    protected function setUp(): void
    {
        parent::setUp();

        // Anonymous spy that captures the Dsn without needing to mock a final class.
        $spy = &$this->lastDsn;
        $factory = new class($spy) implements TransportFactoryInterface {
            public function __construct(private mixed &$captured)
            {
            }

            public function create(Dsn $dsn): TransportInterface
            {
                $this->captured = $dsn;

                return new NullTransport();
            }

            public function supports(Dsn $dsn): bool
            {
                return true;
            }
        };

        $this->driver = new SmtpDriver($factory);
    }

    private function settings(array $values): SettingsRepositoryInterface
    {
        $settings = m::mock(SettingsRepositoryInterface::class);
        $settings->shouldReceive('get')->andReturnUsing(fn ($key) => $values[$key] ?? null);

        return $settings;
    }

    private function buildAndCapture(array $settingValues): Dsn
    {
        $this->driver->buildTransport($this->settings($settingValues));

        $this->assertNotNull($this->lastDsn, 'Factory spy did not capture a Dsn.');

        return $this->lastDsn;
    }

    // -------------------------------------------------------------------------
    // Scheme selection
    // -------------------------------------------------------------------------

    #[Test]
    public function ssl_encryption_uses_smtps_scheme(): void
    {
        $dsn = $this->buildAndCapture(['mail_host' => 'smtp.example.com', 'mail_encryption' => 'ssl']);

        $this->assertSame('smtps', $dsn->getScheme());
    }

    #[Test]
    public function tls_encryption_uses_smtp_scheme(): void
    {
        $dsn = $this->buildAndCapture(['mail_host' => 'smtp.example.com', 'mail_encryption' => 'tls']);

        $this->assertSame('smtp', $dsn->getScheme());
    }

    #[Test]
    public function no_encryption_uses_smtp_scheme(): void
    {
        $dsn = $this->buildAndCapture(['mail_host' => 'smtp.example.com', 'mail_encryption' => '']);

        $this->assertSame('smtp', $dsn->getScheme());
    }

    // -------------------------------------------------------------------------
    // auto_tls (Bug 1: None encryption was silently upgrading to STARTTLS)
    // -------------------------------------------------------------------------

    #[Test]
    public function none_encryption_sets_auto_tls_false(): void
    {
        $dsn = $this->buildAndCapture(['mail_host' => 'smtp.example.com', 'mail_encryption' => '']);

        $this->assertSame('false', $dsn->getOption('auto_tls'));
    }

    #[Test]
    public function tls_encryption_does_not_set_auto_tls(): void
    {
        $dsn = $this->buildAndCapture(['mail_host' => 'smtp.example.com', 'mail_encryption' => 'tls']);

        $this->assertNull($dsn->getOption('auto_tls'));
    }

    #[Test]
    public function ssl_encryption_does_not_set_auto_tls(): void
    {
        $dsn = $this->buildAndCapture(['mail_host' => 'smtp.example.com', 'mail_encryption' => 'ssl']);

        $this->assertNull($dsn->getOption('auto_tls'));
    }

    // -------------------------------------------------------------------------
    // verify_peer (Bug 2: no way to bypass SSL cert verification)
    // -------------------------------------------------------------------------

    #[Test]
    public function verify_peer_disabled_when_setting_is_zero(): void
    {
        $dsn = $this->buildAndCapture([
            'mail_host' => 'smtp.example.com',
            'mail_encryption' => 'tls',
            'mail_smtp_verify_peer' => '0',
        ]);

        $this->assertSame('false', $dsn->getOption('verify_peer'));
    }

    #[Test]
    public function verify_peer_not_set_when_setting_is_one(): void
    {
        $dsn = $this->buildAndCapture([
            'mail_host' => 'smtp.example.com',
            'mail_encryption' => 'tls',
            'mail_smtp_verify_peer' => '1',
        ]);

        $this->assertNull($dsn->getOption('verify_peer'));
    }

    #[Test]
    public function verify_peer_not_set_when_setting_is_absent(): void
    {
        $dsn = $this->buildAndCapture(['mail_host' => 'smtp.example.com', 'mail_encryption' => 'tls']);

        $this->assertNull($dsn->getOption('verify_peer'));
    }

    // -------------------------------------------------------------------------
    // availableSettings
    // -------------------------------------------------------------------------

    #[Test]
    public function available_settings_includes_verify_peer_boolean(): void
    {
        $settings = $this->driver->availableSettings();

        $this->assertArrayHasKey('mail_smtp_verify_peer', $settings);
        $this->assertTrue($settings['mail_smtp_verify_peer']);
    }
}
