<?php namespace Flarum\Mentions\Listeners;

use Flarum\Events\FormatterConfigurator;
use Flarum\Events\FormatterRenderer;
use Flarum\Core\Posts\CommentPost;

class AddPostMentionsFormatter
{
    public function subscribe($events)
    {
        $events->listen(FormatterConfigurator::class, __CLASS__.'@configure');
        $events->listen(FormatterRenderer::class, __CLASS__.'@render');
    }

    public function configure(FormatterConfigurator $event)
    {
        $configurator = $event->configurator;

        $tagName = 'POSTMENTION';

        $tag = $configurator->tags->add($tagName);

        $tag->attributes->add('username');
        $tag->attributes->add('number');
        $tag->attributes->add('id')->filterChain->append('#uint');
        $tag->attributes['id']->required = false;

        $tag->template = '<a href="{$DISCUSSION_URL}{@number}" class="PostMention" data-number="{@number}"><xsl:value-of select="@username"/></a>';
        $tag->filterChain->prepend([static::class, 'addId'])
            ->addParameterByName('post')
            ->setJS('function() { return true; }');

        $configurator->Preg->match('/\B@(?<username>[a-z0-9_-]+)#(?<number>\d+)/i', $tagName);
    }

    public function render(FormatterRenderer $event)
    {
        // TODO: use URL generator
        $event->renderer->setParameter('DISCUSSION_URL', '/d/' . $event->post->discussion_id . '/-/');
    }

    public static function addId($tag, CommentPost $post)
    {
        $id = CommentPost::where('discussion_id', $post->discussion_id)
            ->where('number', $tag->getAttribute('number'))
            ->pluck('id');

        if ($id) {
            $tag->setAttribute('id', $id);

            return true;
        }
    }
}
