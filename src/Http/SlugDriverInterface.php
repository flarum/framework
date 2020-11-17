<?php


namespace Flarum\Http;


use Flarum\Database\AbstractModel;
use Flarum\User\User;

interface SlugDriverInterface
{
    public function toSlug(AbstractModel $instance): string;

    public function fromSlug(string $slug, User $actor): AbstractModel;
}
