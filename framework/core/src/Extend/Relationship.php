<?php namespace Flarum\Extend;

use Illuminate\Foundation\Application;
use Closure;

class Relationship implements ExtenderInterface
{
    protected $parent;

    protected $name;

    protected $type;

    protected $child;

    protected $table;

    public function __construct($parent, $name, $type, $child = null)
    {
        $this->parent = $parent;
        $this->name = $name;
        $this->type = $type;
        $this->child = $child;
    }

    public function extend(Application $app)
    {
        $parent = $this->parent;

        $parent::addRelationship($this->name, function ($model) {
            if ($this->type instanceof Closure) {
                return call_user_func($this->type, $model);
            } elseif ($this->type === 'belongsTo') {
                return $model->belongsTo($this->child, null, null, $this->name);
            } elseif ($this->type === 'belongsToMany') {
                return $model->belongsToMany($this->child, $this->table, null, null, $this->name);
            } else {
                // @todo
            }
        });
    }
}
