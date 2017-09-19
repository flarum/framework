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

use Flarum\Core\User;
use Flarum\Event\ConfigureFormatter;
use Flarum\Event\ConfigureFormatterRenderer;
use Flarum\Forum\UrlGenerator;
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
        $events->listen(ConfigureFormatter::class, [$this, 'configure']);
        $events->listen(ConfigureFormatterRenderer::class, [$this, 'render']);
    }

    /**
     * @param ConfigureFormatter $event
     */
    public function configure(ConfigureFormatter $event)
    {
        $configurator = $event->configurator;

        $configurator->rendering->parameters['PROFILE_URL'] = $this->url->toRoute('user', ['username' => '']);

        $tagName = 'USERMENTION';

        $tag = $configurator->tags->add($tagName);
        $tag->attributes->add('username');
        $tag->attributes->add('displayname');
        $tag->attributes->add('id')->filterChain->append('#uint');

        $tag->template = '<a href="{$PROFILE_URL}{@username}" class="UserMention">@<xsl:value-of select="@displayname"/></a>';
        $tag->filterChain->prepend([static::class, 'addId'])
            ->addParameterByName('userRepository')
            ->setJS('function(tag) { return System.get("flarum/mentions/utils/textFormatter").filterUserMentions(tag); }');

        $configurator->Preg->match('/\B@(?<username>[a-z0-9_-]+)(?!#)/i', $tagName);
    }

    /**
     * @param ConfigureFormatterRenderer $event
     */
    public function render(ConfigureFormatterRenderer $event)
    {
        $post = $event->context;

        $event->xml = Utils::replaceAttributes($event->xml, 'USERMENTION', function ($attributes) use ($post) {
            $user = $post->mentionsUsers->find($attributes['id']);
            if ($user) {
                $attributes['displayname'] = $user->display_name;
            }
            return $attributes;
        });
    }

    /**
     * @param $tag
     * @param UserRepository $users
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
