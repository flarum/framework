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

class MailDriverSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'mail-drivers';

    /**
     * {@inheritdoc}
     *
     * @param \Flarum\Mail\DriverInterface $driver
     * @throws InvalidArgumentException
     */
    protected function getDefaultAttributes($driver)
    {
        if (! ($driver['driver'] instanceof DriverInterface)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.DriverInterface::class
            );
        }

        $driver = $driver['driver'];

        return [
            'fields' => $driver->availableSettings(),
        ];
    }

    public function getId($model)
    {
        return $model['id'];
    }
}
