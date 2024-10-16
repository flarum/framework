<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Mail\DriverInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\MessageBag;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;

class MailTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    #[Test]
    public function drivers_are_unchanged_by_default()
    {
        $response = $this->send(
            $this->request('GET', '/api/mail/settings', [
                'authenticatedAs' => 1,
            ])
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        $fields = json_decode($body, true)['data']['attributes']['fields'];

        // The custom driver does not exist
        $this->assertArrayNotHasKey('custom', $fields);

        // The SMTP driver has its normal fields
        $this->assertEquals([
            'mail_host' => '',
            'mail_port' => '',
            'mail_encryption' => '',
            'mail_username' => '',
            'mail_password' => '',
        ], $fields['smtp']);
    }

    #[Test]
    public function added_driver_appears_in_mail_settings()
    {
        $this->extend(
            (new Extend\Mail)
                ->driver('custom', CustomDriver::class)
        );

        $response = $this->send(
            $this->request('GET', '/api/mail/settings', [
                'authenticatedAs' => 1,
            ])
        );

        $fields = json_decode($response->getBody()->getContents(), true)['data']['attributes']['fields'];

        $this->assertArrayHasKey('custom', $fields);
        $this->assertEquals(['customSetting1' => ''], $fields['custom']);
    }

    #[Test]
    public function adding_driver_with_duplicate_name_overrides_fields()
    {
        $this->extend(
            (new Extend\Mail)
                ->driver('smtp', CustomDriver::class)
        );

        $response = $this->send(
            $this->request('GET', '/api/mail/settings', [
                'authenticatedAs' => 1,
            ])
        );

        $requiredFields = json_decode($response->getBody()->getContents(), true)['data']['attributes']['fields']['smtp'];

        $this->assertEquals(['customSetting1' => ''], $requiredFields);
    }
}

class CustomDriver implements DriverInterface
{
    public function availableSettings(): array
    {
        return ['customSetting1' => ''];
    }

    public function validate(SettingsRepositoryInterface $settings, Factory $validator): MessageBag
    {
        return new MessageBag;
    }

    public function canSend(): bool
    {
        return false;
    }

    public function buildTransport(SettingsRepositoryInterface $settings): TransportInterface
    {
        return new NullTransport();
    }
}
