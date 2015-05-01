<?php namespace Flarum\Api\Actions;

use Tobscure\JsonApi\SerializerInterface;

abstract class SerializeResourceAction extends SerializeAction
{
    /**
     * Serialize the data as appropriate.
     *
     * @param \Tobscure\JsonApi\SerializerInterface $serializer
     * @param array $data
     * @return \Tobscure\JsonApi\Elements\Resource
     */
    protected function serialize(SerializerInterface $serializer, $data)
    {
        return $serializer->resource($data);
    }
}
