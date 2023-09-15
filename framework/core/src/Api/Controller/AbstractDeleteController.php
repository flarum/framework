<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Http\Controller\AbstractController;
use Illuminate\Http\Request;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractDeleteController extends AbstractController
{
    public function __invoke(Request $request): ResponseInterface
    {
        $this->delete($request);

        return new EmptyResponse(204);
    }

    abstract protected function delete(Request $request): void;
}
