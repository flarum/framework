<?php namespace Flarum\Core\Formatter;

use Flarum\Core\Model;
use Misd\Linkify\Linkify;

class LinkifyFormatter extends TextFormatter
{
    /**
     * @var Linkify
     */
    protected $linkify;

    /**
     * @param Linkify $linkify
     */
    public function __construct(Linkify $linkify)
    {
        $this->linkify = $linkify;
    }

    /**
     * {@inheritdoc}
     */
    protected function formatTextBeforePurification($text, Model $post = null)
    {
        return $this->linkify->process($text, ['attr' => ['target' => '_blank']]);
    }
}
