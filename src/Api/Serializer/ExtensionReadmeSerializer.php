<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

class ExtensionReadmeSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($extension)
    {
        $attributes = [
            'content' => $extension->getReadme()
        ];

        return $attributes;
    }

    public function getId($extension)
    {
        return $extension->getId();
    }

    public function getType($extension)
    {
        return 'extension-readmes';
    }
}
