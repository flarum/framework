<?php namespace Flarum\Core\Models;

abstract class ActivityPost extends Post implements MergeableInterface
{
    use MergeableTrait;

    /**
     * Unserialize the content attribute.
     *
     * @param  string  $value
     * @return string
     */
    public function getContentAttribute($value)
    {
        return json_decode($value);
    }

    /**
     * Serialize the content attribute.
     *
     * @param  string  $value
     */
    public function setContentAttribute($value)
    {
        $this->attributes['content'] = json_encode($value);
    }
}
