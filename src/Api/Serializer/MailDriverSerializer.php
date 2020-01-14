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

        $settings = $driver['driver']->availableSettings();

        if (key($settings) === 0) {
            // BACKWARDS COMPATIBILITY: Support a simple list of fields (without
            // type or additional metadata).
            // Turns ["f1", "f2"] into {"f1": "", "f2": ""}
            // @deprecated since 0.1.0-beta.12
            $settings = array_reduce($settings, function ($memo, $key) {
                return [$key => ''] + $memo;
            }, []);
        }

        return [
            'fields' => $settings,
        ];
    }

    public function getId($model)
    {
        return $model['id'];
    }
}
