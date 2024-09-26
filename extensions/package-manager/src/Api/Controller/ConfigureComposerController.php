<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Api\Controller;

use Flarum\ExtensionManager\Composer\ComposerJson;
use Flarum\ExtensionManager\ConfigureAuthValidator;
use Flarum\ExtensionManager\ConfigureComposerValidator;
use Flarum\Foundation\Paths;
use Flarum\Http\RequestUtil;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Used to both set and read the composer.json configuration.
 * And other composer local configuration such as auth.json.
 */
class ConfigureComposerController implements RequestHandlerInterface
{
    protected $configurable = [
        'minimum-stability',
        'repositories',
    ];

    /**
     * @var ConfigureComposerValidator
     */
    protected $composerValidator;

    /**
     * @var ConfigureAuthValidator
     */
    protected $authValidator;

    /**
     * @var Paths
     */
    protected $paths;

    /**
     * @var ComposerJson
     */
    protected $composerJson;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function __construct(ConfigureComposerValidator $composerValidator, ConfigureAuthValidator $authValidator, Paths $paths, ComposerJson $composerJson, Filesystem $filesystem)
    {
        $this->composerValidator = $composerValidator;
        $this->authValidator = $authValidator;
        $this->paths = $paths;
        $this->composerJson = $composerJson;
        $this->filesystem = $filesystem;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $type = Arr::get($request->getParsedBody(), 'type');

        $actor->assertAdmin();

        if (! in_array($type, ['composer', 'auth'])) {
            return new JsonResponse([
                'data' => [],
            ]);
        }

        if ($type === 'composer') {
            $data = $this->composerConfig($request);
        } else {
            $data = $this->authConfig($request);
        }

        return new JsonResponse([
            'data' => $data,
        ]);
    }

    protected function composerConfig(ServerRequestInterface $request): array
    {
        $data = Arr::only(Arr::get($request->getParsedBody(), 'data') ?? [], $this->configurable);

        $this->composerValidator->assertValid($data);
        $composerJson = $this->composerJson->get();

        if (! empty($data)) {
            foreach ($data as $key => $value) {
                Arr::set($composerJson, $key, $value);
            }

            // Always prefer stable releases.
            $composerJson['prefer-stable'] = true;

            $this->composerJson->set($composerJson);
        }

        $default = [
            'minimum-stability' => 'stable',
            'repositories' => [],
        ];

        foreach ($this->configurable as $key) {
            $composerJson[$key] = Arr::get($composerJson, $key, Arr::get($default, $key));

            if (is_null($composerJson[$key])) {
                $composerJson[$key] = $default[$key];
            }
        }

        $composerJson = Arr::sortRecursive($composerJson);

        return Arr::only($composerJson, $this->configurable);
    }

    protected function authConfig(ServerRequestInterface $request): array
    {
        $data = Arr::get($request->getParsedBody(), 'data');

        $this->authValidator->assertValid($data ?? []);

        try {
            $authJson = json_decode($this->filesystem->get($this->paths->base.'/auth.json'), true);
        } catch (FileNotFoundException $e) {
            $authJson = [];
        }

        if (! is_null($data)) {
            foreach ($data as $type => $hosts) {
                foreach ($hosts as $host => $token) {
                    if (empty($token)) {
                        unset($authJson[$type][$host]);
                        continue;
                    }

                    if (str_starts_with($token, 'unchanged:')) {
                        $old = Str::of($token)->explode(':')->skip(1)->values()->all();

                        if (count($old) !== 2) {
                            continue;
                        }

                        [$oldType, $oldHost] = $old;

                        if (! isset($authJson[$oldType][$oldHost])) {
                            continue;
                        }

                        $data[$type][$host] = $authJson[$oldType][$oldHost];
                    } else {
                        $data[$type][$host] = $token;
                    }
                }
            }

            $this->filesystem->put($this->paths->base.'/auth.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $authJson = $data;
        }

        // Remove tokens from response.
        foreach ($authJson as $type => $hosts) {
            foreach ($hosts as $host => $token) {
                $authJson[$type][$host] = "unchanged:$type:$host";
            }
        }

        return $authJson;
    }
}
