<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class AbstractCreateController extends AbstractShowController
{
    public function __invoke(Request $request): JsonResponse
    {
        return parent::__invoke($request)->setStatusCode(201);
    }
}
