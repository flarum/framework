<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Api\Controller;

use Flarum\Foundation\Paths;
use Flarum\Http\RequestUtil;
use Flarum\PackageManager\Composer\ComposerJson;
use Flarum\PackageManager\ConfigureComposerValidator;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
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
    protected $validator;

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

    public function __construct(ConfigureComposerValidator $validator, Paths $paths, ComposerJson $composerJson, Filesystem $filesystem)
    {
        $this->validator = $validator;
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

        $this->validator->assertValid(['composer' => $data]);
        $composerJson = $this->composerJson->get();

        if (! empty($data)) {
            foreach ($data as $key => $value) {
                Arr::set($composerJson, $key, $value);
            }

            // Always prefer stable releases.
            $composerJson['prefer-stable'] = true;

            $this->composerJson->set($composerJson);
        }

        return Arr::only($composerJson, $this->configurable);
    }

    protected function authConfig(ServerRequestInterface $request): array
    {
        $data = Arr::get($request->getParsedBody(), 'data');

        $this->validator->assertValid(['auth' => $data]);

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

                    $data[$type][$host] = $token === '***'
                        ? $authJson[$type][$host]
                        : $token;
                }
            }

            $this->filesystem->put($this->paths->base.'/auth.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $authJson = $data;
        }

        // Remove tokens from response.
        foreach ($authJson as $type => $hosts) {
            foreach ($hosts as $host => $token) {
                $authJson[$type][$host] = '***';
            }
        }

        return $authJson;
    }
}
