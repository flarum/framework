<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Resource;

use Flarum\Api\Endpoint;
use Flarum\Api\Schema;
use Flarum\Extension\Extension;
use Flarum\Extension\ExtensionManager;
use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Resource\Findable;

/**
 * @todo: change to a simple ExtensionResource with readme field.
 *
 * @extends AbstractResource<Extension>
 */
class ExtensionReadmeResource extends AbstractResource implements Findable
{
    public function __construct(
        protected ExtensionManager $extensions
    ) {
    }

    public function type(): string
    {
        return 'extension-readmes';
    }

    /**
     * @param Extension $model
     */
    public function getId(object $model, Context $context): string
    {
        return $model->getId();
    }

    public function find(string $id, Context $context): ?object
    {
        return $this->extensions->getExtension($id);
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Show::make()
                ->admin(),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('content')
                ->get(fn (Extension $extension) => $extension->getReadme()),
        ];
    }
}
