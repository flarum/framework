<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification\Blueprint;

abstract class AbstractBlueprint implements BlueprintInterface
{
    public function getAttributes(): array
    {
        return [
            'type' => static::getType(),
            'from_user_id' => ($fromUser = $this->getFromUser()) ? $fromUser->id : null,
            'subject_id' => ($subject = $this->getSubject()) ? $subject->getKey() : null,
            'data' => ($data = $this->getData()) ? json_encode($data) : null
        ];
    }
}
