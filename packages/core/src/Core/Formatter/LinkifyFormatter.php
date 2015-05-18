<?php namespace Flarum\Core\Formatter;

use Misd\Linkify\Linkify;

class LinkifyFormatter
{
    protected $linkify;

    public function __construct(Linkify $linkify)
    {
        $this->linkify = $linkify;
    }

    public function format($text)
    {
        return $this->linkify->process($text, ['attr' => ['target' => '_blank']]);
    }
}
