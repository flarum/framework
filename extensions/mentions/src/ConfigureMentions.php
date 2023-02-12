<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions;

use Flarum\Group\Group;
use Flarum\Http\UrlGenerator;
use Flarum\Post\PostRepository;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Parser\Tag;

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
        $this->configureGroupMentions($config);
    }

    private function configureUserMentions(Configurator $config): void
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
     * @param Tag $tag
     * @return bool|void
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
            $tag->setAttribute('id', (string) $user->id);
            $tag->setAttribute('displayname', $user->display_name);

            return true;
        }

        $tag->invalidate();
    }

    private function configurePostMentions(Configurator $config): void
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
            ->setJS('function(tag) { return flarum.extensions["flarum-mentions"].filterPostMentions(tag); }')
            ->addParameterByName('actor');

        $config->Preg->match('/\B@["|“](?<displayname>((?!"#[a-z]{0,3}[0-9]+).)+)["|”]#p(?<id>[0-9]+)\b/', $tagName);
    }

    /**
     * @param Tag $tag
     * @return bool|void
     */
    public static function addPostId($tag, User $actor)
    {
        $post = resolve(PostRepository::class)
            ->queryVisibleTo($actor)
            ->find($tag->getAttribute('id'));

        if ($post) {
            $tag->setAttribute('discussionid', (string) $post->discussion_id);
            $tag->setAttribute('number', (string) $post->number);

            if ($post->user) {
                $tag->setAttribute('displayname', $post->user->display_name);
            }

            return true;
        }
    }

    private function configureGroupMentions(Configurator $config)
    {
        $tagName = 'GROUPMENTION';

        $tag = $config->tags->add($tagName);
        $tag->attributes->add('groupname');
        $tag->attributes->add('icon');
        $tag->attributes->add('color');
        $tag->attributes->add('id')->filterChain->append('#uint');

        $tag->template = '
            <xsl:choose>
                <xsl:when test="@deleted != 1">
                    <xsl:choose>
                        <xsl:when test="string(@color) != \'\'">
                            <span class="GroupMention GroupMention--colored" style="--group-color:{@color};">
                                <span class="GroupMention-name">@<xsl:value-of select="@groupname"/></span>
                                <xsl:if test="string(@icon) != \'\'">
                                    <i class="icon {@icon}"></i>
                                </xsl:if>
                            </span>
                        </xsl:when>
                        <xsl:otherwise>
                            <span class="GroupMention">
                                <span class="GroupMention-name">@<xsl:value-of select="@groupname"/></span>
                                <xsl:if test="string(@icon) != \'\'">
                                    <i class="icon {@icon}"></i>
                                </xsl:if>
                            </span>
                        </xsl:otherwise>
                    </xsl:choose>
                </xsl:when>
                <xsl:otherwise>
                    <span class="GroupMention GroupMention--deleted">
                        <span class="GroupMention-name">@<xsl:value-of select="@groupname"/></span>
                        <xsl:if test="string(@icon) != \'\'">
                            <i class="icon {@icon}"></i>
                        </xsl:if>
                    </span>
                </xsl:otherwise>
            </xsl:choose>';
        $tag->filterChain->prepend([static::class, 'addGroupId'])
            ->setJS('function(tag) { return flarum.extensions["flarum-mentions"].filterGroupMentions(tag); }');

        $config->Preg->match('/\B@["|“](?<groupname>((?!"#[a-z]{0,3}[0-9]+).)+)["|”]#g(?<id>[0-9]+)\b/', $tagName);
    }

    /**
     * @param $tag
     * @return bool|void
     */
    public static function addGroupId($tag)
    {
        $group = Group::find($tag->getAttribute('id'));

        if (isset($group) && ! in_array($group->id, [Group::GUEST_ID, Group::MEMBER_ID])) {
            $tag->setAttribute('id', $group->id);
            $tag->setAttribute('groupname', $group->name_plural);
            $tag->setAttribute('icon', $group->icon ?? 'fas fa-at');
            $tag->setAttribute('color', $group->color);

            return true;
        }

        $tag->invalidate();
    }
}
