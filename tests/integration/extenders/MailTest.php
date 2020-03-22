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
use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\Tests\integration\AuthenticatedTestCase;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\MessageBag;
use Swift_NullTransport;
use Swift_Transport;

class MailTest extends AuthenticatedTestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @test
     */
    public function custom_driver_doesnt_exist_by_default()
    {
        $response = $this->send(
            $this->authenticatedRequest('GET', '/api/mail-settings')
        );

        $drivers = json_decode($response->getBody(), true)['data']['attributes']['fields'];

        $this->assertArrayNotHasKey("custom", $drivers);
    }

    /**
     * @test
     */
    public function added_driver_appears_in_mail_settings()
    {
        $this->extend(
            (new Extend\Mail())
                ->addDriver('custom', CustomDriver::class)
        );

        $response = $this->send(
            $this->authenticatedRequest('GET', '/api/mail-settings')
        );

        $drivers = json_decode($response->getBody(), true)['data']['attributes']['fields'];

        $this->assertArrayHasKey("custom", $drivers);
    }

    /**
     * @test
     */
    public function adding_driver_with_duplicate_name_overrides_fields()
    {
        $this->extend(
            (new Extend\Mail())
                ->addDriver('smtp', CustomDriver::class)
        );

        $response = $this->send(
            $this->authenticatedRequest('GET', '/api/mail-settings')
        );

        $requiredFields = json_decode($response->getBody(), true)['data']['attributes']['fields']['smtp'];

        $this->assertEquals($requiredFields, ['customSetting1' => '']);
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

    public function buildTransport(SettingsRepositoryInterface $settings): Swift_Transport
    {
        return new Swift_NullTransport();
    }
}