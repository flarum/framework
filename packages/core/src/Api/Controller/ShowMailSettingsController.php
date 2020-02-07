<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\MailSettingsSerializer;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\AssertPermissionTrait;
use Illuminate\Contracts\Validation\Factory;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ShowMailSettingsController extends AbstractShowController
{
    use AssertPermissionTrait;

    /**
     * {@inheritdoc}
     */
    public $serializer = MailSettingsSerializer::class;

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $this->assertAdmin($request->getAttribute('actor'));

        $drivers = array_map(function ($driver) {
            return self::$container->make($driver);
        }, self::$container->make('mail.supported_drivers'));

        $settings = self::$container->make(SettingsRepositoryInterface::class);
        $configured = self::$container->make('flarum.mail.configured_driver');
        $actual = self::$container->make('mail.driver');
        $validator = self::$container->make(Factory::class);

        if (method_exists($configured, 'validate')) {
            $errors = $configured->validate($settings, $validator);
        } else {
            $errors = new \Illuminate\Support\MessageBag;
        }

        return [
            'drivers' => $drivers,
            'sending' => method_exists($actual, 'canSend') ? $actual->canSend() : true,
            'errors' => $errors,
        ];
    }
}
