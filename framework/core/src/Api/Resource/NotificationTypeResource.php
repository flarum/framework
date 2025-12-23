<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Resource;

use Flarum\Api\Endpoint;
use Flarum\Api\Resource\Contracts\Listable;
use Flarum\Api\Schema;
use Flarum\Extension\ExtensionManager;
use Flarum\Notification\AlertableInterface;
use Flarum\Notification\MailableInterface;
use Flarum\Notification\Notification;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use ReflectionClass;
use Tobyz\JsonApiServer\Context;

/**
 * @property array $resource
 */
class NotificationTypeResource extends AbstractResource implements Listable
{
    public function __construct(
        protected Container $container,
        protected ExtensionManager $extensions
    ) {
    }

    public function type(): string
    {
        return 'notification-types';
    }

    public function getId(object $model, Context $context): string
    {
        return $model->id;
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Index::make()
                ->authenticated()
                ->admin(),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('type')
                ->get(fn (object $resource) => $resource->type),

            Schema\Str::make('blueprintClass')
                ->get(fn (object $resource) => $resource->blueprintClass),

            Schema\Str::make('subjectModel')
                ->get(fn (object $resource) => $resource->subjectModel)
                ->nullable(),

            Schema\Arr::make('defaultDrivers')
                ->get(fn (object $resource) => $resource->defaultDrivers),

            Schema\Arr::make('capabilities')
                ->get(fn (object $resource) => $resource->capabilities),

            Schema\Arr::make('emailViews')
                ->get(fn (object $resource) => $resource->emailViews)
                ->nullable(),

            Schema\Str::make('extension')
                ->get(fn (object $resource) => $resource->extension)
                ->nullable(),
        ];
    }

    public function query(Context $context): object
    {
        /** @var \Flarum\Api\Context $context */
        $context->getActor()->assertAdmin();

        // Return a simple array wrapper object that we can iterate over
        return (object) [
            'items' => $this->getNotificationTypes($context)
        ];
    }

    public function results(object $query, Context $context): iterable
    {
        return $query->items;
    }

    public function filters(): array
    {
        return [];
    }

    public function sorts(): array
    {
        return [];
    }

    public function resolveSorts(): array
    {
        return [];
    }

    protected function getNotificationTypes(Context $context): array
    {
        /** @var \Flarum\Api\Context $context */
        $blueprints = $this->container->make('flarum.notification.blueprints');
        $subjectModels = Notification::getSubjectModels();

        $types = [];

        foreach ($blueprints as $blueprintClass => $defaultDrivers) {
            $reflection = new ReflectionClass($blueprintClass);
            $type = $blueprintClass::getType();

            $capabilities = [
                'alert' => $reflection->implementsInterface(AlertableInterface::class),
                'email' => $reflection->implementsInterface(MailableInterface::class),
            ];

            $emailViews = null;
            if ($capabilities['email']) {
                try {
                    // Create a temporary instance to get email views
                    // This is safe as we're only reading metadata
                    $tempInstance = $this->createMockBlueprint($blueprintClass);
                    $emailViews = $tempInstance->getEmailViews();
                } catch (\Exception $e) {
                    // If we can't instantiate, skip email views
                    $emailViews = null;
                }
            }

            $types[] = (object) [
                'id' => $type,
                'type' => $type,
                'blueprintClass' => $blueprintClass,
                'subjectModel' => $subjectModels[$type] ?? null,
                'defaultDrivers' => $defaultDrivers,
                'capabilities' => $capabilities,
                'emailViews' => $emailViews,
                'extension' => $this->getExtensionForBlueprint($blueprintClass),
            ];
        }

        return $types;
    }

    /**
     * Determine which extension provides this blueprint.
     */
    protected function getExtensionForBlueprint(string $blueprintClass): ?string
    {
        // Core blueprints
        if (str_starts_with($blueprintClass, 'Flarum\\Notification\\Blueprint\\')) {
            return null; // Core
        }

        // Try to find the extension by checking if the class file is in an extension directory
        try {
            $reflection = new ReflectionClass($blueprintClass);
            $classFile = $reflection->getFileName();

            if ($classFile) {
                // Check each extension to see if this class belongs to it
                foreach ($this->extensions->getExtensions() as $extension) {
                    if (str_starts_with($classFile, $extension->getPath())) {
                        return $extension->getId();
                    }
                }

                // If not found in enabled extensions, try to extract from composer path
                // e.g., "/path/vendor/fof/byobu/..." -> "fof-byobu"
                if (preg_match('/\/vendor\/([^\/]+)\/([^\/]+)\//', $classFile, $matches)) {
                    $vendor = $matches[1];
                    $package = $matches[2];
                    return "$vendor-$package";
                }
            }
        } catch (\ReflectionException $e) {
            // If we can't reflect the class, fall back to namespace parsing
        }

        // Fallback: Extract extension name from namespace
        // For Flarum core extensions: "Flarum\Mentions\..." -> "flarum-mentions"
        if (preg_match('/^Flarum\\\\([^\\\\]+)\\\\/', $blueprintClass, $matches)) {
            $extensionName = $matches[1];
            // Convert PascalCase to kebab-case
            $extensionName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $extensionName));
            return 'flarum-' . $extensionName;
        }

        // For third-party extensions: "FoF\Byobu\..." -> extract from namespace
        // "FoF\Byobu\..." -> "fof-byobu"
        if (preg_match('/^([^\\\\]+)\\\\([^\\\\]+)\\\\/', $blueprintClass, $matches)) {
            $vendor = strtolower($matches[1]);
            $package = $matches[2];
            // Convert PascalCase to kebab-case
            $package = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $package));
            return "$vendor-$package";
        }

        return 'unknown';
    }

    /**
     * Create a mock blueprint instance for reading metadata.
     * This creates minimal mock data to instantiate the blueprint.
     */
    protected function createMockBlueprint(string $blueprintClass): object
    {
        // Use reflection to determine what the constructor needs
        $reflection = new ReflectionClass($blueprintClass);
        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            return new $blueprintClass();
        }

        // Create mock arguments based on type hints
        $args = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();

            if ($type && !$type->isBuiltin()) {
                $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;

                if ($typeName) {
                    // Create a mock instance of the required type
                    try {
                        $typeReflection = new ReflectionClass($typeName);
                        if (!$typeReflection->isInstantiable()) {
                            $args[] = null;
                        } else {
                            // Create a minimal instance using newInstanceWithoutConstructor
                            $args[] = $typeReflection->newInstanceWithoutConstructor();
                        }
                    } catch (\Exception $e) {
                        $args[] = null;
                    }
                } else {
                    $args[] = null;
                }
            } else {
                $args[] = null;
            }
        }

        return new $blueprintClass(...$args);
    }
}
