<?php namespace Flarum\Extend;

use Illuminate\Contracts\Container\Container;

class ApiAction implements ExtenderInterface
{
    protected $action;

    protected $serializer;

    protected $addInclude = [];

    protected $removeInclude = [];

    protected $addLink = [];

    protected $removeLink = [];

    protected $limitMax;

    protected $limit;

    protected $addSortFields = [];

    protected $removeSortFields = [];

    protected $sort;

    public function __construct($action)
    {
        $this->action = $action;
    }

    public function serializer($serializer)
    {
        $this->serializer = $serializer;

        return $this;
    }

    public function addInclude($relation, $default = true)
    {
        $this->addInclude[] = compact('relation', 'default');

        return $this;
    }

    public function removeInclude($relation)
    {
        $this->removeInclude[] = $relation;

        return $this;
    }

    public function addLink($relation)
    {
        $this->addLink[] = $relation;

        return $this;
    }

    public function removeLink($relation)
    {
        $this->removeLink[] = $relation;

        return $this;
    }

    public function limitMax($limitMax)
    {
        $this->limitMax = $limitMax;

        return $this;
    }

    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    public function addSortField($field)
    {
        $this->addSortFields[] = $field;

        return $this;
    }

    public function removeSortField($field)
    {
        $this->removeSortFields[] = $field;

        return $this;
    }

    public function sort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    public function extend(Container $container)
    {
        foreach ((array) $this->action as $action) {
            if ($this->serializer) {
                $action::$serializer = $this->serializer;
            }
            foreach ($this->addInclude as $include) {
                $action::$include[$include['relation']] = $include['default'];
            }
            foreach ($this->removeInclude as $relation) {
                unset($action::$include[$relation]);
            }
            foreach ($this->addLink as $relation) {
                $action::$link[] = $relation;
            }
            foreach ($this->removeLink as $relation) {
                if (($k = array_search($relation, $action::$link)) !== false) {
                    unset($action::$link[$k]);
                }
            }
            if ($this->limitMax) {
                $action::$limitMax = $this->limitMax;
            }
            if ($this->limit) {
                $action::$limit = $this->limit;
            }
            foreach ($this->addSortFields as $field) {
                $action::$sortFields[] = $field;
            }
            foreach ($this->removeSortFields as $field) {
                if (($k = array_search($field, $action::$sortFields)) !== false) {
                    unset($action::$sortFields[$k]);
                }
            }
            if ($this->sort) {
                $action::$sort = $this->sort;
            }
        }
    }
}
