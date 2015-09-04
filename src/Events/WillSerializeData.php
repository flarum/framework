<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Events;

use Flarum\Api\Actions\Action;
use Flarum\Api\JsonApiRequest;

class WillSerializeData
{
    /**
     * @var Action
     */
    public $action;

    /**
     * @var mixed
     */
    public $data;

    /**
     * @var JsonApiRequest
     */
    public $request;

    public function __construct(Action $action, &$data, JsonApiRequest $request)
    {
        $this->action = $action;
        $this->data = &$data;
        $this->request = $request;
    }
}
