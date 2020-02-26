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
    /**
     * {@inheritdoc}
     */
    protected $type = 'mail-settings';

    /**
     * {@inheritdoc}
     *
     * @param array $settings
     * @throws InvalidArgumentException
     */
    protected function getDefaultAttributes($settings)
    {
        return [
            'fields' => array_map([$this, 'serializeDriver'], $settings['drivers']),
            'sending' => $settings['sending'],
            'errors' => $settings['errors'],
        ];
    }

    private function serializeDriver(DriverInterface $driver)
    {
        $settings = $driver->availableSettings();

        if (key($settings) === 0) {
            // BACKWARDS COMPATIBILITY: Support a simple list of fields (without
            // type or additional metadata).
            // Turns ["f1", "f2"] into {"f1": "", "f2": ""}
            // @deprecated since 0.1.0-beta.12
            $settings = array_reduce($settings, function ($memo, $key) {
                return [$key => ''] + $memo;
            }, []);
        }

        return $settings;
    }

    public function getId($model)
    {
        return 'global';
    }
}
