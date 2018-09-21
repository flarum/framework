<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Mentions\Listener;

use Flarum\Formatter\Event\Configuring;
use Flarum\Formatter\Event\Rendering;
use Flarum\Http\UrlGenerator;
use Flarum\Post\CommentPost;
use Illuminate\Contracts\Events\Dispatcher;
use s9e\TextFormatter\Utils;

class FormatPostMentions
{
    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @param UrlGenerator $url
     */
    public function __construct(UrlGenerator $url)
    {
        $this->url = $url;
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(Configuring::class, [$this, 'configure']);
        $events->listen(Rendering::class, [$this, 'render']);
    }

    /**
     * @param Configuring $event
     */
    public function configure(Configuring $event)
    {
        $configurator = $event->configurator;

        $configurator->rendering->parameters['DISCUSSION_URL'] = $this->url->to('forum')->route('discussion', ['id' => '']);

        $tagName = 'POSTMENTION';

        $tag = $configurator->tags->add($tagName);

        $tag->attributes->add('username');
        $tag->attributes->add('displayname');
        $tag->attributes->add('number')->filterChain->append('#uint');
        $tag->attributes->add('discussionid')->filterChain->append('#uint');
        $tag->attributes->add('id')->filterChain->append('#uint');

        $tag->template = '<a href="{$DISCUSSION_URL}{@discussionid}/{@number}" class="PostMention" data-id="{@id}"><xsl:value-of select="@displayname"/></a>';

        $tag->filterChain
            ->prepend([static::class, 'addId'])
            ->setJS('function(tag) { return flarum.extensions["flarum-mentions"].filterPostMentions(tag); }');

        $configurator->Preg->match('/\B@(?<username>[a-z0-9_-]+)#(?<id>\d+)/i', $tagName);
    }

    /**
     * @param Rendering $event
     */
    public function render(Rendering $event)
    {
        $post = $event->context;

        $event->xml = Utils::replaceAttributes($event->xml, 'POSTMENTION', function ($attributes) use ($post) {
            $post = $post->mentionsPosts->find($attributes['id']);
            if ($post && $post->user) {
                $attributes['displayname'] = $post->user->display_name;
            }

            return $attributes;
        });
    }

    /**
     * @param $tag
     * @return bool
     */
    public static function addId($tag)
    {
        $post = CommentPost::find($tag->getAttribute('id'));

        if ($post) {
            $tag->setAttribute('discussionid', (int) $post->discussion_id);
            $tag->setAttribute('number', (int) $post->number);
            $tag->setAttribute('displayname', $post->user->display_name);

            return true;
        }
    }
}
