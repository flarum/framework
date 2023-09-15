<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Http\Controller\AbstractController;
use Flarum\Http\RequestUtil;
use Flarum\Settings\Event;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;

class SetSettingsController extends AbstractController
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected Dispatcher $dispatcher
    ) {
    }

    public function __invoke(Request $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $settings = $request->json()->all();

        $this->dispatcher->dispatch(new Event\Saving($settings));

        foreach ($settings as $k => $v) {
            $this->dispatcher->dispatch(new Event\Serializing($k, $v));

            $this->settings->set($k, $v);
        }

        $this->dispatcher->dispatch(new Event\Saved($settings));

        return new EmptyResponse();
    }
}
