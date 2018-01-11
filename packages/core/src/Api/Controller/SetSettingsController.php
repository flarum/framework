<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Http\Controller\ControllerInterface;
use Flarum\Settings\Event\Saved;
use Flarum\Settings\Event\Serializing;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\AssertPermissionTrait;
use Illuminate\Contracts\Events\Dispatcher;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;

class SetSettingsController implements ControllerInterface
{
    use AssertPermissionTrait;

    /**
     * @var \Flarum\Settings\SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(SettingsRepositoryInterface $settings, Dispatcher $dispatcher)
    {
        $this->settings = $settings;
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request)
    {
        $this->assertAdmin($request->getAttribute('actor'));

        $settings = $request->getParsedBody();

        foreach ($settings as $k => $v) {
            $this->dispatcher->fire(new Serializing($k, $v));

            $this->settings->set($k, $v);

            $this->dispatcher->fire(new Saved($k, $v));
        }

        return new EmptyResponse(204);
    }
}
