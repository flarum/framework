<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\Api;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Throwable;

/**
 * A PATCH that merges an idea runs in one transaction. The merge locks both
 * ideas before the discussion is saved and holds the locks to the end, and a
 * refused merge rolls back the request's other changes (a new title, say)
 * instead of saving them beside a 422.
 *
 * Only a merge: a status change pushes the voters' notification job, which a
 * queue worker could pick up before an open transaction commits.
 */
class MergeInOneTransaction implements Middleware
{
    public function __construct(
        protected ConnectionInterface $db
    ) {
    }

    public function process(Request $request, Handler $handler): Response
    {
        if ($request->getAttribute('routeName') !== 'discussions.update'
            || ! array_key_exists('ideationMergedIntoId', (array) Arr::get((array) $request->getParsedBody(), 'data.attributes'))) {
            return $handler->handle($request);
        }

        $this->db->beginTransaction();

        try {
            $response = $handler->handle($request);
        } catch (Throwable $e) {
            $this->db->rollBack();

            throw $e;
        }

        // An error the handler answered itself rolls back as a thrown one does.
        $response->getStatusCode() >= 400 ? $this->db->rollBack() : $this->db->commit();

        return $response;
    }
}
