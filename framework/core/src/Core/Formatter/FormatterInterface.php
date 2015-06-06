<?php namespace Flarum\Core\Formatter;

use Flarum\Core\Models\Post;

interface FormatterInterface
{
    public function beforePurification($text, Post $post = null);

    public function afterPurification($text, Post $post = null);
}
