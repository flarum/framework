<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Api\Controller;

use Composer\Semver\VersionParser;
use Flarum\ExtensionManager\Discover\Cache;
use Flarum\ExtensionManager\Discover\Compatibility;
use Flarum\ExtensionManager\Discover\Packagist;
use Flarum\ExtensionManager\Host\StorageDir;
use Flarum\Foundation\Application;
use Flarum\Foundation\Paths;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * "Does this work with the Flarum I actually have?", for one page of results.
 *
 * 🚨 Answered against the INSTALLED core version, not against "Flarum 2". A
 * forum on 2.0.0-rc.8 and one on 2.1.0 get different answers, and an extension
 * requiring ^2.1 offered to the first would fail at the resolve — which is
 * exactly the experience being replaced.
 */
class CompatController implements RequestHandlerInterface
{
    /** A page of results, and a hard ceiling on the work one request can ask for. */
    private const MOST = 24;

    public function __construct(
        private Paths $paths,
        private Application $app,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $names = array_values(array_filter(
            (array) Arr::get((array) $request->getParsedBody(), 'packages', []),
            fn ($n) => is_string($n) && preg_match('#^[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*$#', $n)
        ));

        if ($names === []) {
            return new JsonResponse(['verdicts' => []]);
        }

        /*
         * 🚨 Capped. This makes one outbound HTTP call per uncached package, and
         * an unbounded list from the browser would be an unbounded number of
         * requests leaving somebody's server — the kind of thing that becomes an
         * outage rather than a slow page.
         */
        $names = array_slice($names, 0, self::MOST);

        /*
         * 🚨 An optional target, so the core pre-flight can ask "would this
         * extension work on the Flarum I am considering" through the same
         * endpoint. Validated with Composer's parser rather than trusted:
         * it reaches a cache key and a constraint check, and junk would fill
         * the cache directory with entries nothing ever reads again.
         */
        $core = $this->targetOr($this->coreVersion(), Arr::get((array) $request->getParsedBody(), 'core'));

        $packagist = new Packagist(new Cache(StorageDir::of($this->paths->storage).'/packagist'));

        return new JsonResponse([
            'verdicts' => $packagist->verdicts($names, new Compatibility($core)),
            'core' => $core,
        ]);
    }

    private function targetOr(string $fallback, mixed $given): string
    {
        if (! is_string($given) || $given === '') {
            return $fallback;
        }

        try {
            (new VersionParser())->normalize($given);
        } catch (\UnexpectedValueException) {
            return $fallback;
        }

        return $given;
    }

    private function coreVersion(): string
    {
        // Application::version() is what this forum is actually running, and it
        // is the only version any of this should be answered against.
        return ltrim($this->app->version(), 'v');
    }
}
