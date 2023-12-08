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

    public function __construct(ConfigureComposerValidator $validator, Paths $paths)
    {
        $this->validator = $validator;
        $this->paths = $paths;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $data = Arr::only(Arr::get($request->getParsedBody(), 'data'), $this->configurable);

        $actor->assertAdmin();

        $this->validator->assertValid($data);
        $composerJson = $this->readComposerJson();

        if (! empty($data)) {
            foreach ($data as $key => $value) {
                Arr::set($composerJson, $key, $value);
            }

            $this->writeComposerJson($composerJson);
        }

        return new JsonResponse([
            'data' => Arr::only($composerJson, $this->configurable),
        ]);
    }

    protected function readComposerJson()
    {
        $composerJson = file_get_contents($this->paths->base.'/composer.json');
        $composerJson = json_decode($composerJson, true);

        return $composerJson;
    }

    protected function writeComposerJson($composerJson)
    {
        $composerJson = json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($this->paths->base.'/composer.json', $composerJson);
    }
}
