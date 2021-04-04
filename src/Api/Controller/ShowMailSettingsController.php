<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\MailSettingsSerializer;
use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Validation\Factory;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ShowMailSettingsController extends AbstractShowController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = MailSettingsSerializer::class;

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        RequestUtil::getActor($request)->assertAdmin();

        $drivers = array_map(function ($driver) {
            return self::$container->make($driver);
        }, self::$container->make('mail.supported_drivers'));

        $settings = self::$container->make(SettingsRepositoryInterface::class);
        $configured = self::$container->make('flarum.mail.configured_driver');
        $actual = self::$container->make('mail.driver');
        $validator = self::$container->make(Factory::class);

        $errors = $configured->validate($settings, $validator);

        return [
            'drivers' => $drivers,
            'sending' => $actual->canSend(),
            'errors' => $errors,
        ];
    }
}
