<?php namespace Flarum\Core\Models;

trait MergeableTrait
{
    public function saveAfter(Model $previous)
    {
        if ($previous instanceof static) {
            if ($this->mergeInto($previous)) {
                $previous->save();
            } else {
                $previous->delete();
            }

            return $previous;
        }

        $this->save();

        return $this;
    }

    abstract protected function mergeInto(Model $previous);
}
