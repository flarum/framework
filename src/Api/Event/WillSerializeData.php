<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Event;

use Flarum\Api\Controller\AbstractSerializeController;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class WillSerializeData
{
    /**
     * @var AbstractSerializeController
     */
    public $controller;

    /**
     * @var mixed
     */
    public $data;

    /**
     * @var ServerRequestInterface
     */
    public $request;

    /**
     * @var Document
     */
    public $document;

    /**
     * @var \Flarum\User\User
     */
    public $actor;

    /**
     * @param AbstractSerializeController $controller
     * @param mixed $data
     * @param ServerRequestInterface $request
     * @param Document $document
     */
    public function __construct(
        AbstractSerializeController $controller,
        &$data,
        ServerRequestInterface $request,
        Document $document
    ) {
        $this->controller = $controller;
        $this->data = &$data;
        $this->request = $request;
        $this->document = $document;
        $this->actor = $request->getAttribute('actor');
    }

    /**
     * @param string $controller
     * @return bool
     */
    public function isController($controller)
    {
        return $this->controller instanceof $controller;
    }
}
