<?php namespace Flarum\Api\Actions;

class ApiParams
{
    protected $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function get($key, $default = null)
    {
        return array_get($this->params, $key, $default);
    }

    public function range($key, $default = null, $min = null, $max = null)
    {
        $value = (int) $this->get($key, $default);

        if (! is_null($min)) {
            $value = max($value, $min);
        }
        if (! is_null($max)) {
            $value = min($value, $max);
        }
        return $value;
    }

    public function included($available)
    {
        $requested = explode(',', $this->get('include'));
        return array_intersect((array) $available, $requested);
    }

    // public function explodeIds($ids)
    // {
    //     return array_unique(array_map('intval', array_filter(explode(',', $ids))));
    // }

    public function in($key, $options)
    {
        $value = $this->get($key);

        if (array_key_exists($key, $options)) {
            return $options[$key];
        }
        if (! in_array($value, $options)) {
            $value = reset($options);
        }

        return $value;
    }

    public function sort($options)
    {
        $criteria = (string) $this->get('sort', '');
        $order = null;

        if ($criteria && $criteria[0] == '-') {
            $order = 'desc';
            $criteria = substr($criteria, 1);
        }

        if (! in_array($criteria, $options)) {
            $criteria = reset($options);
        }

        if ($criteria && ! $order) {
            $order = 'asc';
        }

        return [
            'field' => $criteria,
            'order' => $order,
            'string' => ($order == 'desc' ? '-' : '').$criteria
        ];
    }

    public function start()
    {
        return $this->range('start', 0, 0);
    }

    public function count($default, $max = 100)
    {
        return $this->range('count', $default, 1, $max);
    }
}
