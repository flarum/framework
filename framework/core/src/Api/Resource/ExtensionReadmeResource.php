<?php

namespace Flarum\Api\Resource;

use Flarum\Api\Endpoint;
use Flarum\Api\Resource\Contracts\Findable;
use Flarum\Api\Schema;
use Flarum\Extension\Extension;
use Flarum\Extension\ExtensionManager;
use Flarum\Mail\DriverInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Validation\Factory;
use stdClass;
use Tobyz\JsonApiServer\Context;

/**
 * @todo: change to a simple ExtensionResource with readme field.
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
