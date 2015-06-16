<?php namespace Flarum\Extend;

use Illuminate\Foundation\Application;

class ApiLink implements ExtenderInterface
{
    protected $actions;

    protected $relationships;

    public function __construct($actions, $relationships)
    {
        $this->actions = $actions;
        $this->relationships = $relationships;
    }

    public function extend(Application $app)
    {
        foreach ((array) $this->actions as $action) {
            $parts = explode('.', $action);
            $class = 'Flarum\Api\Actions\\'.ucfirst($parts[0]).'\\'.ucfirst($parts[1]).'Action';

            foreach ((array) $this->relationships as $relationship) {
                $class::$link[] = $relationship;
            }
        }
    }
}
