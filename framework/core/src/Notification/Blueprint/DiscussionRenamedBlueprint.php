<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification\Blueprint;

use Flarum\Database\AbstractModel;
use Flarum\Discussion\Discussion;
use Flarum\Post\DiscussionRenamedPost;
use Flarum\User\User;

class DiscussionRenamedBlueprint implements BlueprintInterface
{
    public function __construct(
        protected DiscussionRenamedPost $post
    ) {
    }

    public function getFromUser(): ?User
    {
        return $this->post->user;
    }

    public function getSubject(): ?AbstractModel
    {
        return $this->post->discussion;
    }

    public function getData(): array
    {
        return ['postNumber' => (int) $this->post->number];
    }

    public static function getType(): string
    {
        return 'discussionRenamed';
    }

    public static function getSubjectModel(): string
    {
        return Discussion::class;
    }
}
