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
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ConfigureComposerController implements RequestHandlerInterface
{
    protected $configurable = [
        'minimum-stability',
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

    public function __construct(ConfigureComposerValidator $validator, Paths $paths, ComposerJson $composerJson)
    {
        $this->validator = $validator;
        $this->paths = $paths;
        $this->composerJson = $composerJson;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $data = Arr::only(Arr::get($request->getParsedBody(), 'data'), $this->configurable);

        $actor->assertAdmin();

        $this->validator->assertValid($data);
        $composerJson = $this->composerJson->get();

        if (! empty($data)) {
            foreach ($data as $key => $value) {
                Arr::set($composerJson, $key, $value);
            }

            $this->composerJson->set($composerJson);
        }

        return new JsonResponse([
            'data' => Arr::only($composerJson, $this->configurable),
        ]);
    }
}
