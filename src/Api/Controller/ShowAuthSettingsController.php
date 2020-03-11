<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\AuthSettingsSerializer;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\AssertPermissionTrait;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ShowAuthSettingsController extends AbstractShowController
{
    use AssertPermissionTrait;

    /**
     * {@inheritdoc}
     */
    public $serializer = AuthSettingsSerializer::class;

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $this->assertAdmin($request->getAttribute('actor'));

        $driverIds = self::$container->make('flarum.auth.supported_drivers');

        $settings = self::$container->make(SettingsRepositoryInterface::class);

        $drivers = [];
        foreach ($driverIds as $driverId => $driver) {
            $drivers[$driverId] = [
                "enabled" => $settings->get('auth_driver_enabled_'.$driverId, false),
                "trust_emails" => $settings->get('auth_driver_trust_emails_' . $driverId, false)
            ];
        }

        return [
            'drivers' => $drivers,
        ];
    }
}
