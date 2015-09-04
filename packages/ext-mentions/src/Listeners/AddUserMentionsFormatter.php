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
use Flarum\Events\FormatterRenderer;
use Flarum\Events\FormatterParser;
use Flarum\Core\Users\UserRepository;

class AddUserMentionsFormatter
{
    protected $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    public function subscribe($events)
    {
        $events->listen(FormatterConfigurator::class, [$this, 'configure']);
        $events->listen(FormatterParser::class, [$this, 'parse']);
        $events->listen(FormatterRenderer::class, [$this, 'render']);
    }

    public function configure(FormatterConfigurator $event)
    {
        $configurator = $event->configurator;

        $tagName = 'USERMENTION';

        $tag = $configurator->tags->add($tagName);
        $tag->attributes->add('username');
        $tag->attributes->add('id')->filterChain->append('#uint');
        $tag->attributes['id']->required = false;

        $tag->template = '<a href="{$PROFILE_URL}{@username}" class="UserMention">@<xsl:value-of select="@username"/></a>';
        $tag->filterChain->prepend([static::class, 'addId'])
            ->addParameterByName('userRepository')
            ->setJS('function() { return true; }');

        $configurator->Preg->match('/\B@(?<username>[a-z0-9_-]+)(?!#)/i', $tagName);
    }

    public function parse(FormatterParser $event)
    {
        $event->parser->registeredVars['userRepository'] = $this->users;
    }

    public function render(FormatterRenderer $event)
    {
        // TODO: use URL generator
        $event->renderer->setParameter('PROFILE_URL', '/u/');
    }

    public static function addId($tag, UserRepository $users)
    {
        if ($id = $users->getIdForUsername($tag->getAttribute('username'))) {
            $tag->setAttribute('id', $id);

            return true;
        }
    }
}
