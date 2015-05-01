<?php namespace Flarum\Api;

class JsonApiRequest extends Request
{
    /**
     * @var array
     */
    public $sort;

    /**
     * @var array
     */
    public $include;

    /**
     * @var array
     */
    public $link;

    /**
     * @var int
     */
    public $limit;

    /**
     * @var int
     */
    public $offset;
}
