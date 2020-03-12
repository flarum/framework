<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\AuthSettingsSerializer;
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

        $drivers = self::$container->make('flarum.auth.supported_drivers');

        $drivers = array_map(function ($driver) {
            return self::$container->make($driver)->meta();
        }, $drivers);

        return [
            'drivers' => $drivers,
        ];
    }
}
