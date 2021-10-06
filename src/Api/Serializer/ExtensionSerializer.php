<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

class ExtensionSerializer extends BasicExtensionSerializer
{
    /**
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($extension)
    {
        $attributes = parent::getDefaultAttributes($extension) + [
            'readmeHtml' => $extension->getReadme()
        ];

        return $attributes;
    }
}
