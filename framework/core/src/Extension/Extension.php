<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extension;

use Flarum\Database\Migrator;
use Flarum\Extend\LifecycleInterface;
use Flarum\Extension\Exception\ExtensionBootError;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Filesystem\Filesystem as FilesystemInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use s9e\TextFormatter\Bundles\Fatdown;
use Throwable;

/**
 * @property string $name
 * @property string $description
 * @property string $type
 * @property array  $keywords
 * @property string $homepage
 * @property string $time
 * @property string $license
 * @property array  $authors
 * @property array  $support
 * @property array  $require
 * @property array  $requireDev
 * @property array  $autoload
 * @property array  $autoloadDev
 * @property array  $conflict
 * @property array  $replace
 * @property array  $provide
 * @property array  $suggest
 * @property array  $extra
 */
class Extension implements Arrayable
{
    public const LOGO_MIMETYPES = [
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
    ];

    /**
     * Unique ID of the extension.
     * Constructed from vendor/packageName to vendor-packageName.
     *
     * @example flarum-suspend
     */
    protected string $id;

    /**
     * The IDs of all Flarum extensions that this extension depends on.
     *
     * @var string[]
     */
    protected array $extensionDependencyIds;

    /**
     * The IDs of all Flarum extensions that this extension should be booted after
     * if enabled.
     *
     * @var string[]
     */
    protected array $optionalDependencyIds;

    protected bool $installed = true;
    protected string $version;

    /**
     * Whether the composer package is marked as abandoned.
     * If true, it may contain the name of a replacement package.
     *
     * @var bool|string
     */
    protected bool|string $abandoned = false;

    public function __construct(
        protected string $path,
        protected array $composerJson
    ) {
        $this->assignId();
    }

    public static function nameToId(string $name): string
    {
        [$vendor, $package] = explode('/', $name);
        $package = str_replace(['flarum-ext-', 'flarum-'], '', $package);

        return "$vendor-$package";
    }

    /**
     * Assigns the id for the extension used globally.
     */
    protected function assignId(): void
    {
        $this->id = static::nameToId($this->name);
    }

    /**
     * @internal
     */
    public function extend(Container $container): void
    {
        foreach ($this->getExtenders() as $extender) {
            try {
                $extender->extend($container, $this);
            } catch (Throwable $e) {
                throw new ExtensionBootError($this, $extender, $e);
            }
        }
    }

    public function __get(string $name): mixed
    {
        return $this->composerJsonAttribute(Str::snake($name, '-'));
    }

    public function __isset(string $name): bool
    {
        return isset($this->{$name}) || $this->composerJsonAttribute(Str::snake($name, '-'));
    }

    /**
     * Dot notation getter for composer.json attributes.
     *
     * @see https://laravel.com/docs/11.x/helpers#arrays
     */
    public function composerJsonAttribute(string $name): mixed
    {
        return Arr::get($this->composerJson, $name);
    }

    /**
     * @internal
     */
    public function setInstalled(bool $installed): static
    {
        $this->installed = $installed;

        return $this;
    }

    public function isInstalled(): bool
    {
        return $this->installed;
    }

    /**
     * @internal
     */
    public function setVersion(string $version): static
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @param bool|string $abandoned
     *
     * @internal
     */
    public function setAbandoned(bool|string $abandoned): static
    {
        $this->abandoned = $abandoned;

        return $this;
    }

    /**
     * Get the list of flarum extensions that this extension depends on.
     *
     * @param array<string, mixed> $extensionSet: An associative array where keys are the composer package names
     *                             of installed extensions. Used to figure out which dependencies
     *                             are flarum extensions.
     * @internal
     */
    public function calculateDependencies(array $extensionSet): void
    {
        $this->extensionDependencyIds = (new Collection(Arr::get($this->composerJson, 'require', [])))
            ->keys()
            ->filter(function ($key) use ($extensionSet) {
                return array_key_exists($key, $extensionSet);
            })
            ->map(function ($key) {
                return static::nameToId($key);
            })
            ->toArray();

        $this->optionalDependencyIds = (new Collection(Arr::get($this->composerJson, 'extra.flarum-extension.optional-dependencies', [])))
            ->map(function ($key) {
                return static::nameToId($key);
            })
            ->toArray();
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * Check if the composer package is marked as abandoned.
     *
     * @return bool
     */
    public function isAbandoned(): bool
    {
        return (bool) $this->abandoned;
    }

    /**
     * Get the abandoned status.
     * Returns false if not abandoned, or a string with the replacement package name if abandoned.
     *
     * @return bool|string
     */
    public function getAbandoned(): bool|string
    {
        return $this->abandoned;
    }

    public function getIcon(): ?array
    {
        $icon = $this->composerJsonAttribute('extra.flarum-extension.icon');
        $file = Arr::get($icon, 'image');

        if (is_null($icon) || is_null($file)) {
            return $icon;
        }

        $file = $this->path.'/'.$file;

        if (file_exists($file)) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            if (! array_key_exists($extension, self::LOGO_MIMETYPES)) {
                throw new \RuntimeException('Invalid image type');
            }

            $mimetype = self::LOGO_MIMETYPES[$extension];
            $data = base64_encode(file_get_contents($file));

            $icon['backgroundImage'] = "url('data:$mimetype;base64,$data')";
        }

        return $icon;
    }

    public function getIconStyles(): string
    {
        $properties = $this->getIcon();

        if (empty($properties)) {
            return '';
        }

        $properties = array_filter($properties, function ($item) {
            return is_string($item);
        });

        unset($properties['name']);

        return implode(';', array_map(function (string $property, string $value) {
            $property = Str::kebab($property);

            return "$property: $value";
        }, array_keys($properties), $properties));
    }

    /**
     * @internal
     */
    public function enable(Container $container): void
    {
        foreach ($this->getLifecycleExtenders() as $extender) {
            $extender->onEnable($container, $this);
        }
    }

    /**
     * @internal
     */
    public function disable(Container $container): void
    {
        foreach ($this->getLifecycleExtenders() as $extender) {
            $extender->onDisable($container, $this);
        }
    }

    /**
     * The raw path of the directory under extensions.
     */
    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->composerJsonAttribute('extra.flarum-extension.title');
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * The IDs of all Flarum extensions that this extension depends on.
     */
    public function getExtensionDependencyIds(): array
    {
        return $this->extensionDependencyIds;
    }

    /**
     * The IDs of all Flarum extensions that this extension should be booted after
     * if enabled.
     */
    public function getOptionalDependencyIds(): array
    {
        return $this->optionalDependencyIds;
    }

    private function getExtenders(): array
    {
        $extenderFile = $this->getExtenderFile();

        if (! $extenderFile) {
            return [];
        }

        $extenders = require $extenderFile;

        if (! is_array($extenders)) {
            $extenders = [$extenders];
        }

        return Arr::flatten($extenders);
    }

    /**
     * @return LifecycleInterface[]
     */
    private function getLifecycleExtenders(): array
    {
        return array_filter(
            $this->getExtenders(),
            function ($extender) {
                return $extender instanceof LifecycleInterface;
            }
        );
    }

    private function getExtenderFile(): ?string
    {
        $filename = "$this->path/extend.php";

        if (file_exists($filename)) {
            return $filename;
        }

        return null;
    }

    /**
     * Compile a list of links for this extension.
     */
    public function getLinks(): array
    {
        $links = [];

        if (($sourceUrl = $this->composerJsonAttribute('source.url')) || ($sourceUrl = $this->composerJsonAttribute('support.source'))) {
            $links['source'] = $sourceUrl;
        }

        if ($discussUrl = $this->composerJsonAttribute('support.forum')) {
            $links['discuss'] = $discussUrl;
        }

        if ($documentationUrl = $this->composerJsonAttribute('support.docs')) {
            $links['documentation'] = $documentationUrl;
        }

        if ($websiteUrl = $this->composerJsonAttribute('homepage')) {
            $links['website'] = $websiteUrl;
        }

        if ($supportEmail = $this->composerJsonAttribute('support.email')) {
            $links['support'] = "mailto:$supportEmail";
        }

        if (($funding = $this->composerJsonAttribute('funding')) && is_array($funding) && ($fundingUrl = Arr::get($funding, '0.url'))) {
            $links['donate'] = $fundingUrl;
        }

        $links['authors'] = [];

        foreach ((array) $this->composerJsonAttribute('authors') as $author) {
            $links['authors'][] = [
                'name' => Arr::get($author, 'name'),
                'link' => Arr::get($author, 'homepage') ?? (Arr::get($author, 'email') ? 'mailto:'.Arr::get($author, 'email') : ''),
            ];
        }

        return array_merge($links, $this->composerJsonAttribute('extra.flarum-extension.links') ?? []);
    }

    /**
     * Tests whether the extension has assets.
     */
    public function hasAssets(): bool
    {
        return realpath($this->path.'/assets/') !== false;
    }

    /**
     * @internal
     */
    public function copyAssetsTo(FilesystemInterface $target): void
    {
        if (! $this->hasAssets()) {
            return;
        }

        $source = new Filesystem();

        $assetFiles = $source->allFiles("$this->path/assets");

        foreach ($assetFiles as $fullPath) {
            $relPath = substr($fullPath, strlen("$this->path/assets"));
            $target->put("extensions/$this->id/$relPath", $source->get($fullPath));
        }
    }

    /**
     * Tests whether the extension has migrations.
     */
    public function hasMigrations(): bool
    {
        return realpath($this->path.'/migrations/') !== false;
    }

    /**
     * @internal
     */
    public function migrate(Migrator $migrator, string $direction = 'up'): ?int
    {
        if (! $this->hasMigrations()) {
            return null;
        }

        if ($direction === 'up') {
            $migrator->run($this->getPath().'/migrations', $this);
        } else {
            return $migrator->reset($this->getPath().'/migrations', $this);
        }

        return null;
    }

    /**
     * Generates an array result for the object.
     */
    public function toArray(): array
    {
        return array_merge($this->composerJson, [
            'id' => $this->getId(),
            'version' => $this->getVersion(),
            'path' => $this->getPath(),
            'icon' => $this->getIcon(),
            'hasAssets' => $this->hasAssets(),
            'hasMigrations' => $this->hasMigrations(),
            'extensionDependencyIds' => $this->getExtensionDependencyIds(),
            'optionalDependencyIds' => $this->getOptionalDependencyIds(),
            'links' => $this->getLinks(),
            'abandoned' => $this->getAbandoned(),
        ]);
    }

    /**
     * Gets the rendered contents of the extension README file as a HTML string.
     */
    public function getReadme(): ?string
    {
        $content = null;

        if (file_exists($file = "$this->path/README.md")) {
            $content = file_get_contents($file);
        } elseif (file_exists($file = "$this->path/README")) {
            $content = file_get_contents($file);
        }

        if ($content) {
            $xml = Fatdown::parse($content);

            return Fatdown::render($xml);
        }

        return null;
    }
}
