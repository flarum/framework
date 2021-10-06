<?php

namespace Flarum\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;

class ReadmeSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'readme';

    /**
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($readme): array
    {
        $attributes = [];

        if ($this->actor->isAdmin() && !empty($readme->content)) {
            $attributes = [
                'content' => $readme->content,
            ];
        }

        return $attributes;
    }
}
