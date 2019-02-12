<?php

namespace Flarum\Http\Event;

class RenderMaintenancePage
{
    /**
     * @var string
     */
    public $view;
    /**
     * HTTP status code
     *
     * @var int
     */
    public $code;

    public function __construct(string $view, int $code = 503)
    {
        $this->view = $view;
        $this->code = $code;
    }
}
