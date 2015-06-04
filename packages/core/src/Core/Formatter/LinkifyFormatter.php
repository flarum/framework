<?php namespace Flarum\Core\Formatter;

use Flarum\Core\Models\Post;
use Misd\Linkify\Linkify;

class LinkifyFormatter extends FormatterAbstract
{
    protected $linkify;

    public function __construct(Linkify $linkify)
    {
        $this->linkify = $linkify;
    }

    public function beforePurification($text, Post $post = null)
    {
        return $this->linkify->process($text, ['attr' => ['target' => '_blank']]);
    }
}
