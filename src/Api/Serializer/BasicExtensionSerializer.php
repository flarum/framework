<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

use Flarum\Extension\Extension;
use Flarum\Extension\ExtensionManager;
use InvalidArgumentException;

class BasicExtensionSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'extensions';

    /**
     * @var ExtensionManager
     */
    protected $extensionManager;

    public function __construct(ExtensionManager $extensionManager)
    {
        $this->extensionManager = $extensionManager;
    }

    protected function getDefaultAttributes($extension)
    {
        if (! ($extension instanceof Extension)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.Extension::class
            );
        }

        return (array) array_merge([
            'id'                     => $extension->getId(),
            'version'                => $extension->getVersion(),
            'path'                   => $extension->getPath(),
            'icon'                   => $extension->getIcon(),
            'hasAssets'              => $extension->hasAssets(),
            'hasMigrations'          => $extension->hasMigrations(),
            'extensionDependencyIds' => $extension->getExtensionDependencyIds(),
            'optionalDependencyIds'  => $extension->getOptionalDependencyIds(),
            'links'                  => $extension->getLinks(),
        ], $extension->getComposerJson());
    }
}
