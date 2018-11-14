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
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use s9e\TextFormatter\Utils;

class FormatUserMentions
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

        $configurator->rendering->parameters['PROFILE_URL'] = $this->url->to('forum')->route('user', ['username' => '']);

        $tagName = 'USERMENTION';

        $tag = $configurator->tags->add($tagName);
        $tag->attributes->add('username');
        $tag->attributes->add('displayname');
        $tag->attributes->add('id')->filterChain->append('#uint');

        $tag->template = '<a href="{$PROFILE_URL}{@username}" class="UserMention">@<xsl:value-of select="@displayname"/></a>';
        $tag->filterChain->prepend([static::class, 'addId'])
            ->setJS('function(tag) { return flarum.extensions["flarum-mentions"].filterUserMentions(tag); }');

        $configurator->Preg->match('/\B@(?<username>[a-z0-9_-]+)(?!#)/i', $tagName);
    }

    /**
     * @param Rendering $event
     */
    public function render(Rendering $event)
    {
        $post = $event->context;

        $event->xml = Utils::replaceAttributes($event->xml, 'USERMENTION', function ($attributes) use ($post) {
            $user = $post->mentionsUsers->find($attributes['id']);
            if ($user) {
                $attributes['username'] = $user->username;
                $attributes['displayname'] = $user->display_name;
            }

            return $attributes;
        });
    }

    /**
     * @param $tag
     *
     * @return bool
     */
    public static function addId($tag)
    {
        if ($user = User::where('username', 'like', $tag->getAttribute('username'))->first()) {
            $tag->setAttribute('id', $user->id);
            $tag->setAttribute('displayname', $user->display_name);

            return true;
        }
    }
}
