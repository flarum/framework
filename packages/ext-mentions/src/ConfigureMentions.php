<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions;

use Flarum\Http\UrlGenerator;
use Flarum\Post\CommentPost;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use s9e\TextFormatter\Configurator;

class ConfigureMentions
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

    public function __invoke(Configurator $config)
    {
        $this->configureUserMentions($config);
        $this->configurePostMentions($config);
    }

    private function configureUserMentions(Configurator $config)
    {
        $config->rendering->parameters['PROFILE_URL'] = $this->url->to('forum')->route('user', ['username' => '']);

        $tagName = 'USERMENTION';

        $tag = $config->tags->add($tagName);
        $tag->attributes->add('displayname');
        $tag->attributes->add('id')->filterChain->append('#uint');

        $tag->template = '
            <xsl:choose>
                <xsl:when test="@deleted != 1">
                    <a href="{$PROFILE_URL}{@slug}" class="UserMention">@<xsl:value-of select="@displayname"/></a>
                </xsl:when>
                <xsl:otherwise>
                    <span class="UserMention UserMention--deleted">@<xsl:value-of select="@displayname"/></span>
                </xsl:otherwise>
            </xsl:choose>';
        $tag->filterChain->prepend([static::class, 'addUserId'])
            ->setJS('function(tag) { return flarum.extensions["flarum-mentions"].filterUserMentions(tag); }');

        $config->Preg->match('/\B@["|“](?<displayname>((?!"#[a-z]{0,3}[0-9]+).)+)["|”]#(?<id>[0-9]+)\b/', $tagName);
        $config->Preg->match('/\B@(?<username>[a-z0-9_-]+)(?!#)/i', $tagName);
    }

    /**
     * @param $tag
     *
     * @return bool
     */
    public static function addUserId($tag)
    {
        $allow_username_format = (bool) resolve(SettingsRepositoryInterface::class)->get('flarum-mentions.allow_username_format');

        if ($tag->hasAttribute('username') && $allow_username_format) {
            $user = User::where('username', $tag->getAttribute('username'))->first();
        } elseif ($tag->hasAttribute('id')) {
            $user = User::find($tag->getAttribute('id'));
        }

        if (isset($user)) {
            $tag->setAttribute('id', $user->id);
            $tag->setAttribute('displayname', $user->display_name);

            return true;
        }

        $tag->invalidate();
    }

    private function configurePostMentions(Configurator $config)
    {
        $config->rendering->parameters['DISCUSSION_URL'] = $this->url->to('forum')->route('discussion', ['id' => '']);

        $tagName = 'POSTMENTION';

        $tag = $config->tags->add($tagName);

        $tag->attributes->add('displayname');
        $tag->attributes->add('number')->filterChain->append('#uint');
        $tag->attributes->add('discussionid')->filterChain->append('#uint');
        $tag->attributes->add('id')->filterChain->append('#uint');

        $tag->template = '
            <xsl:choose>
                <xsl:when test="@deleted != 1">
                    <a href="{$DISCUSSION_URL}{@discussionid}/{@number}" class="PostMention" data-id="{@id}"><xsl:value-of select="@displayname"/></a>
                </xsl:when>
                <xsl:otherwise>
                    <span class="PostMention PostMention--deleted" data-id="{@id}"><xsl:value-of select="@displayname"/></span>
                </xsl:otherwise>
            </xsl:choose>';

        $tag->filterChain
            ->prepend([static::class, 'addPostId'])
            ->setJS('function(tag) { return flarum.extensions["flarum-mentions"].filterPostMentions(tag); }');

        $config->Preg->match('/\B@["|“](?<displayname>((?!"#[a-z]{0,3}[0-9]+).)+)["|”]#p(?<id>[0-9]+)\b/', $tagName);
    }

    /**
     * @param $tag
     * @return bool
     */
    public static function addPostId($tag)
    {
        $post = CommentPost::find($tag->getAttribute('id'));

        if ($post && $post->user) {
            $tag->setAttribute('discussionid', (int) $post->discussion_id);
            $tag->setAttribute('number', (int) $post->number);
            $tag->setAttribute('displayname', $post->user->display_name);

            return true;
        }
    }
}
