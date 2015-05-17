<?php namespace Flarum\Extend;

use Illuminate\Foundation\Application;

class ApiInclude implements ExtenderInterface
{
    protected $actions;

    protected $relationships;

    protected $status;

    public function __construct($actions, $relationships, $status = false)
    {
        $this->actions = $actions;
        $this->relationships = $relationships;
        $this->status = $status;
    }

    public function extend(Application $app)
    {
        foreach ((array) $this->actions as $action) {
            $parts = explode('.', $action);
            $class = 'Flarum\Api\Actions\\'.ucfirst($parts[0]).'\\'.ucfirst($parts[1]).'Action';

            foreach ((array) $this->relationships as $relationship) {
                if (is_null($this->status)) {
                    unset($class::$include[$relationship]);
                } else {
                    $class::$include[$relationship] = $this->status;
                }
            }
        }
    }
}
