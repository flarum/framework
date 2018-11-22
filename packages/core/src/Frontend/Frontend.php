<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Frontend;

use Flarum\Api\Client;
use Flarum\Api\Controller\ShowForumController;
use Illuminate\Contracts\View\Factory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Frontend
{
    /**
     * @var Factory
     */
    protected $view;

    /**
     * @var Client
     */
    protected $api;

    /**
     * @var callable[]
     */
    protected $content = [];

    public function __construct(Factory $view, Client $api)
    {
        $this->view = $view;
        $this->api = $api;
    }

    /**
     * @param callable $content
     */
    public function content(callable $content)
    {
        $this->content[] = $content;
    }

    public function document(Request $request): Document
    {
        $forumDocument = $this->getForumDocument($request);

        $document = new Document($this->view, $forumDocument);

        $this->populate($document, $request);

        return $document;
    }

    protected function populate(Document $document, Request $request)
    {
        foreach ($this->content as $content) {
            $content($document, $request);
        }
    }

    private function getForumDocument(Request $request): array
    {
        $actor = $request->getAttribute('actor');

        $this->api->setErrorHandler(null);

        return $this->getResponseBody(
            $this->api->send(ShowForumController::class, $actor)
        );
    }

    private function getResponseBody(Response $response): array
    {
        return json_decode($response->getBody(), true);
    }
}
