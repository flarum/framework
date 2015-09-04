<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Mentions\Listeners;

use Flarum\Events\FormatterConfigurator;
use Flarum\Core\Posts\CommentPost;

class AddPostMentionsFormatter
{
    public function subscribe($events)
    {
        $events->listen(FormatterConfigurator::class, [$this, 'configure']);
    }

    public function configure(FormatterConfigurator $event)
    {
        $configurator = $event->configurator;

        $tagName = 'POSTMENTION';

        $tag = $configurator->tags->add($tagName);

        $tag->attributes->add('username');
        $tag->attributes->add('number')->filterChain->append('#uint');
        $tag->attributes->add('discussionid')->filterChain->append('#uint');
        $tag->attributes->add('id')->filterChain->append('#uint');
        $tag->attributes['number']->required = false;
        $tag->attributes['discussionid']->required = false;

        $tag->template = '<a href="/d/{@discussionid}/{@number}" class="PostMention" data-id="{@id}"><xsl:value-of select="@username"/></a>';

        $tag->filterChain
            ->prepend([static::class, 'addId'])
            ->setJS('function() { return true; }');

        $configurator->Preg->match('/\B@(?<username>[a-z0-9_-]+)#(?<id>\d+)/i', $tagName);
    }

    public static function addId($tag)
    {
        $post = CommentPost::find($tag->getAttribute('id'));

        if ($post) {
            $tag->setAttribute('discussionid', (int) $post->discussion_id);
            $tag->setAttribute('number', (int) $post->number);

            return true;
        }
    }
}
