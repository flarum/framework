<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Admin\Middleware;

use Flarum\Core\Access\Gate;
use Illuminate\Contracts\Container\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Flarum\Core\Exception\PermissionDeniedException;
use Zend\Stratigility\MiddlewareInterface;

class RequireAdministrateAbility implements MiddlewareInterface
{
    /**
     * @var Gate
     */
    protected $gate;

    /**
     * @param Gate $gate
     */
    public function __construct(Gate $gate)
    {
        $this->gate = $gate;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        $actor = $request->getAttribute('actor');

        if (! $this->gate->forUser($actor)->allows('administrate')) {
            throw new PermissionDeniedException;
        }

        return $out ? $out($request, $response) : $response;
    }
}
