<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Collection;
use Tobscure\JsonApi\Document;
use Tobscure\JsonApi\ElementInterface;
use Tobscure\JsonApi\SerializerInterface;

abstract class AbstractListController extends AbstractSerializeController
{
    protected function createElement(mixed $data, SerializerInterface $serializer): ElementInterface
    {
        return new Collection($data, $serializer);
    }

    abstract protected function data(ServerRequestInterface $request, Document $document): iterable;

    protected function addPaginationData(Document $document, ServerRequestInterface $request, string $url, ?int $total): void
    {
        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);

        $document->addPaginationLinks(
            $url,
            $request->getQueryParams(),
            $offset,
            $limit,
            $total,
        );

        $document->setMeta([
            'total' => $total,
            'perPage' => $limit,
            'page' => $offset / $limit + 1,
        ]);
    }
}
