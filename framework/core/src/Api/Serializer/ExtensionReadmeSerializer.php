<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

use Flarum\Extension\Extension;

class ExtensionReadmeSerializer extends AbstractSerializer
{
    /**
     * @param Extension $model
     */
    protected function getDefaultAttributes(object|array $model): array
    {
        return [
            'content' => $model->getReadme()
        ];
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
