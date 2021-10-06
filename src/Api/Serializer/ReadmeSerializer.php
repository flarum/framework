<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

class ReadmeSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'readme';

    /**
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($readme): array
    {
        $attributes = [];

        if ($this->actor->isAdmin() && ! empty($readme->content)) {
            $attributes = [
                'content' => $readme->content,
            ];
        }

        return $attributes;
    }
}
