<?php namespace Flarum\Api\Actions;

use Tobscure\JsonApi\SerializerInterface;

abstract class SerializeCollectionAction extends SerializeAction
{
    /**
     * Serialize the data as appropriate.
     *
     * @param SerializerInterface $serializer
     * @param array $data
     * @return \Tobscure\JsonApi\Elements\Collection
     */
    protected function serialize(SerializerInterface $serializer, $data)
    {
        return $serializer->collection($data);
    }
}
