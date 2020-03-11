<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

use InvalidArgumentException;

class AuthSettingsSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'auth-settings';

    /**
     * {@inheritdoc}
     *
     * @param array $settings
     * @throws InvalidArgumentException
     */
    protected function getDefaultAttributes($settings)
    {
        return [
            'drivers' => $settings['drivers'],
        ];
    }

    public function getId($model)
    {
        return 'global';
    }
}
