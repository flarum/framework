<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Resource;

use Flarum\Api\Endpoint;
use Flarum\Api\Resource\Contracts\Findable;
use Flarum\Api\Schema;
use Flarum\Mail\DriverInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Validation\Factory;
use stdClass;
use Tobyz\JsonApiServer\Context;

/**
 * @extends AbstractResource<object>
 */
class MailSettingResource extends AbstractResource implements Findable
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected Factory $validator,
        protected Container $container
    ) {
    }

    public function type(): string
    {
        return 'mail-settings';
    }

    public function getId(object $model, Context $context): string
    {
        return '1';
    }

    public function id(Context $context): ?string
    {
        return '1';
    }

    public function find(string $id, Context $context): ?object
    {
        return new stdClass();
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Show::make()
                ->route('GET', '/')
                ->admin(),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Arr::make('fields')
                ->get(function () {
                    return array_map(fn (DriverInterface $driver) => $driver->availableSettings(), array_map(function ($driver) {
                        return $this->container->make($driver);
                    }, $this->container->make('mail.supported_drivers')));
                }),
            Schema\Boolean::make('sending')
                ->get(function () {
                    /** @var DriverInterface $actual */
                    $actual = $this->container->make('mail.driver');

                    return $actual->canSend();
                }),
            Schema\Arr::make('errors')
                ->get(function () {
                    /** @var DriverInterface $configured */
                    $configured = $this->container->make('flarum.mail.configured_driver');

                    return $configured->validate($this->settings, $this->validator);
                }),
        ];
    }
}
