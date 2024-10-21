<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Http\RequestUtil;
use Flarum\Settings\Event;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SetSettingsController implements RequestHandlerInterface
{
    public static array $resetWhen = [];

    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected Dispatcher $dispatcher
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $settings = $request->getParsedBody();

        $this->dispatcher->dispatch(new Event\Saving($settings));

        foreach ($settings as $k => $v) {
            $this->dispatcher->dispatch(new Event\Serializing($k, $v));

            if (! is_null($resetWhen = Arr::get(static::$resetWhen, $k)) && $resetWhen($v)) {
                $this->settings->delete($k);
            } else {
                $this->settings->set($k, $v);
            }
        }

        $this->dispatcher->dispatch(new Event\Saved($settings));

        return new EmptyResponse(204);
    }
}
