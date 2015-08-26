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

use Flarum\Api\Actions\SerializeAction;

class BuildApiAction
{
    public $action;

    /**
     * @param SerializeAction $action
     */
    public function __construct($action)
    {
        $this->action = $action;
    }

    public function serializer($serializer)
    {
        $this->action->serializer = $serializer;
    }

    public function addInclude($relation, $default = true)
    {
        $this->action->include[$relation] = $default;
    }

    public function removeInclude($relation)
    {
        unset($this->action->include[$relation]);
    }

    public function addLink($relation)
    {
        $this->action->link[] = $relation;
    }

    public function removeLink($relation)
    {
        if (($k = array_search($relation, $this->action->link)) !== false) {
            unset($this->action->link[$k]);
        }
    }

    public function limitMax($limitMax)
    {
        $this->action->limitMax = $limitMax;
    }

    public function limit($limit)
    {
        $this->action->limit = $limit;
    }

    public function addSortField($field)
    {
        $this->action->sortFields[] = $field;
    }

    public function removeSortField($field)
    {
        if (($k = array_search($field, $this->action->sortFields)) !== false) {
            unset($this->action->sortFields[$k]);
        }
    }

    public function sort($sort)
    {
        $this->action->sort = $sort;
    }
}
