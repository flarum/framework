<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

use Flarum\Mail\DriverInterface;
use InvalidArgumentException;

class MailSettingsSerializer extends AbstractSerializer
{
    protected $type = 'mail-settings';

    /**
     * @throws InvalidArgumentException
     */
    protected function getDefaultAttributes(object|array $model): array
    {
        return [
            'fields' => array_map([$this, 'serializeDriver'], $model['drivers']),
            'sending' => $model['sending'],
            'errors' => $model['errors'],
        ];
    }

    private function serializeDriver(DriverInterface $driver): array
    {
        return $driver->availableSettings();
    }

    public function getId($model)
    {
        return 'global';
    }
}
