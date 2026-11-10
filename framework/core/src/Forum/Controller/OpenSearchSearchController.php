<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Forum\OpenSearch\OpenSearchDescription;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Where a browser sends a search from its address bar. A browser keeps the
 * address it was given when the forum was added, so this one stays the same
 * whichever page is the homepage, and sends the search on to the discussion
 * list wherever it is now.
 */
class OpenSearchSearchController implements RequestHandlerInterface
{
    public function __construct(
        protected OpenSearchDescription $description
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $q = Arr::get($request->getQueryParams(), 'q');
        $q = is_string($q) ? trim($q) : '';

        // Browsers send a space as "+", which the frontend would keep.
        $query = $q === '' ? '' : '?q='.rawurlencode($q);

        return new RedirectResponse($this->description->searchPage().$query);
    }
}
