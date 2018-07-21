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

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\HtmlResponse;

class Controller implements RequestHandlerInterface
{
    /**
     * @var HtmlDocumentFactory
     */
    protected $document;

    /**
     * @param HtmlDocumentFactory $document
     */
    public function __construct(HtmlDocumentFactory $document)
    {
        $this->document = $document;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request): Response
    {
        return new HtmlResponse(
            $this->document->make($request)->render()
        );
    }
}
