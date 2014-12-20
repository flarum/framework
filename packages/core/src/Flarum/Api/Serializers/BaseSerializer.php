<?php namespace Flarum\Api\Serializers;

use Tobscure\JsonApi\SerializerAbstract;
use Event;

/**
 * A base serializer to call Flarum events at common serialization points.
 */
abstract class BaseSerializer extends SerializerAbstract
{
    /**
     * The name to use for Flarum events.
     * @var string
     */
    protected static $eventName;

    /**
     * Fire an event to allow default links and includes to be changed upon
     * serializer instantiation.
     *
     * @param array $include
     */
    public function __construct($include = null, $link = null)
    {
        parent::__construct($include, $link);
        Event::fire('Flarum.Api.Serializers.'.static::$eventName.'.Initialize', [$this]);
    }

    /**
     * Fire an event to allow custom serialization of attributes.
     * 
     * @param  mixed $model The model to serialize.
     * @param  array $attributes Attributes that have already been serialized.
     * @return array
     */
    protected function attributesEvent($model, $attributes = [])
    {
        if (static::$eventName) {
            Event::fire('Flarum.Api.Serializers.'.static::$eventName.'.Attributes', [$model, &$attributes]);
        }
        return $attributes;
    }

    /**
     * Fire an event to allow custom URL templates to be specified.
     * 
     * @param  array $href URL templates that have already been specified.
     * @return array
     */
    protected function hrefEvent($href = [])
    {
        if (static::$eventName) {
            Event::fire('Flarum.Api.Serializers.'.static::$eventName.'.Href', [&$href]);
        }
        return $href;
    }

    /**
     * Generate a URL for a certain controller action.
     *
     * @param string $controllerMethod The name of the controller and its
     * method, separated by '@'. eg. UsersController@show
     *
     * @param array $params An array of route parameters to fill.
     * @return string
     */
    protected function action($controllerMethod, $params)
    {
        $controllerPrefix = '\\Flarum\\Api\\Controllers\\';

        // For compatibility with JSON-API, serializers will usually pass a
        // param containing a value of, for example, {discussions.id}. This is
        // problematic because after substituting named parameters, Laravel
        // substitutes remaining {placeholders} sequentially (thus removing
        // {discussions.id} from the URL.) To work around this, we opt to
        // initially replace parameters with an asterisk, and afterwards swap
        // the asterisk for the {discussions.id} placeholder.
        
        $starredParams = array_combine(
            array_keys($params),
            array_fill(0, count($params), '*')
        );
        // $url = action($controllerPrefix.$controllerMethod, $starredParams);
        $url = '';

        return preg_replace_sub('/\*/', $params, $url);
    }

    /**
     * Fire an event to allow for custom links and includes.
     *
     * @param string $name
     * @param array $arguments
     * @return void
     */
    public function __call($name, $arguments)
    {
        if (static::$eventName && (substr($name, 0, 4) == 'link' || substr($name, 0, 7) == 'include')) {
            Event::fire('Flarum.Api.Serializers.'.static::$eventName.'.'.ucfirst($name), $arguments);
        }
    }
}
