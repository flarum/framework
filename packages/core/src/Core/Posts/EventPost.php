<?php namespace Flarum\Core\Posts;

abstract class EventPost extends Post
{
    /**
     * Unserialize the content attribute from the database's JSON value.
     *
     * @param string $value
     * @return string
     */
    public function getContentAttribute($value)
    {
        return json_decode($value, true);
    }

    /**
     * Serialize the content attribute to be stored in the database as JSON.
     *
     * @param string $value
     */
    public function setContentAttribute($value)
    {
        $this->attributes['content'] = json_encode($value);
    }
}
