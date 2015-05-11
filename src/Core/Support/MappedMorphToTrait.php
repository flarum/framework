<?php namespace Flarum\Core\Support;

trait MappedMorphToTrait
{
    public function mappedMorphTo($classes, $name = null, $type = null, $id = null)
    {
        // If no name is provided, we will use the backtrace to get the function name
        // since that is most likely the name of the polymorphic interface. We can
        // use that to get both the class and foreign key that will be utilized.
        if (is_null($name)) {
            list(, $caller) = debug_backtrace(false, 2);

            $name = snake_case($caller['function']);
        }

        list($type, $id) = $this->getMorphs($name, $type, $id);

        // If the type value is null it is probably safe to assume we're eager loading
        // the relationship. When that is the case we will pass in a dummy query as
        // there are multiple types in the morph and we can't use single queries.
        if (is_null($typeValue = $this->$type)) {
            return new MappedMorphTo(
                $this->newQuery(), $this, $id, null, $type, $name, $classes
            );
        }

        // If we are not eager loading the relationship we will essentially treat this
        // as a belongs-to style relationship since morph-to extends that class and
        // we will pass in the appropriate values so that it behaves as expected.
        else {
            $class = $classes[$typeValue];
            $instance = new $class;

            return new MappedMorphTo(
                $instance->newQuery(), $this, $id, $instance->getKeyName(), $type, $name, $classes
            );
        }
    }
}
