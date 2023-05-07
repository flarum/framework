<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions;

use Flarum\Extension\ExtensionManager;
use Flarum\Group\Group;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tags\Tag;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Collection;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Parser\Tag as FormatterTag;

/**
 * @TODO: refactor this lump of code into a mentionable models polymorphic system (for v2.0).
 */
class ConfigureMentions
{
    public const USER_MENTION_WITH_DISPLAY_NAME_REGEX = '/\B@["“](?<displayname>((?!"#[a-z]{0,3}[0-9]+).)+)["”]#(?<id>[0-9]+)\b/';
    public const USER_MENTION_WITH_USERNAME_REGEX = '/\B@(?<username>[a-z0-9_-]+)(?!#)/i';
    public const POST_MENTION_WITH_DISPLAY_NAME_REGEX = '/\B@["“](?<displayname>((?!"#[a-z]{0,3}[0-9]+).)+)["”]#p(?<id>[0-9]+)\b/';
    public const GROUP_MENTION_WITH_NAME_REGEX = '/\B@["“](?<groupname>((?!"#[a-z]{0,3}[0-9]+).)+)["|”]#g(?<id>[0-9]+)\b/';
    public const TAG_MENTION_WITH_SLUG_REGEX = '/(?:[^“"]|^)\B#(?<slug>[-_\p{L}\p{N}\p{M}]+)\b/ui';

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @var ExtensionManager
     */
    protected $extensions;

    public function __construct(UrlGenerator $url, ExtensionManager $extensions)
    {
        $this->url = $url;
        $this->extensions = $extensions;
    }

    public function __invoke(Configurator $config)
    {
        $this->configureUserMentions($config);
        $this->configurePostMentions($config);
        $this->configureGroupMentions($config);

        if ($this->extensions->isEnabled('flarum-tags')) {
            $this->configureTagMentions($config);
        }
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
            ->setJS('function(tag) { return flarum.extensions["flarum-mentions"].filterUserMentions(tag); }')
            ->addParameterByName('mentions');

        $tag->filterChain->append([static::class, 'dummyFilter'])
            ->setJs('function(tag) { return flarum.extensions["flarum-mentions"].postFilterUserMentions(tag); }');

        $config->Preg->match(self::USER_MENTION_WITH_DISPLAY_NAME_REGEX, $tagName);
        $config->Preg->match(self::USER_MENTION_WITH_USERNAME_REGEX, $tagName);
    }

    /**
     * @param FormatterTag $tag
     * @param array<string, Collection> $mentions
     * @return bool|void
     */
    public static function addUserId($tag, array $mentions)
    {
        $allow_username_format = (bool) resolve(SettingsRepositoryInterface::class)->get('flarum-mentions.allow_username_format');

        if ($tag->hasAttribute('username') && $allow_username_format) {
            $user = $mentions['users']->where('username', $tag->getAttribute('username'))->first();
        } elseif ($tag->hasAttribute('id')) {
            $user = $mentions['users']->where('id', $tag->getAttribute('id'))->first();
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
            ->addParameterByName('mentions');

        $tag->filterChain->append([static::class, 'dummyFilter'])
            ->setJs('function(tag) { return flarum.extensions["flarum-mentions"].postFilterPostMentions(tag); }');

        $config->Preg->match(self::POST_MENTION_WITH_DISPLAY_NAME_REGEX, $tagName);
    }

    /**
     * @param FormatterTag $tag
     * @param array<string, Collection> $mentions
     * @return bool|void
     */
    public static function addPostId($tag, array $mentions)
    {
        $post = $mentions['posts']->where('id', $tag->getAttribute('id'))->first();

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
        $tag->attributes->add('id')->filterChain->append('#uint');

        $tag->template = '
            <xsl:choose>
                <xsl:when test="@deleted != 1">
                    <xsl:choose>
                        <xsl:when test="string(@color) != \'\'">
                            <span class="GroupMention GroupMention--colored" style="--color:{@color};">
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
            ->setJS('function(tag) { return flarum.extensions["flarum-mentions"].filterGroupMentions(tag); }')
            ->addParameterByName('actor')
            ->addParameterByName('mentions');

        $tag->filterChain->append([static::class, 'dummyFilter'])
            ->setJS('function(tag) { return flarum.extensions["flarum-mentions"].postFilterGroupMentions(tag); }');

        $config->Preg->match(self::GROUP_MENTION_WITH_NAME_REGEX, $tagName);
    }

    /**
     * @param FormatterTag $tag
     * @param User $actor
     * @param array<string, Collection> $mentions
     * @return bool|void
     */
    public static function addGroupId(FormatterTag $tag, User $actor, array $mentions)
    {
        $id = $tag->getAttribute('id');

        if ($actor->cannot('mentionGroups') || in_array($id, [Group::GUEST_ID, Group::MEMBER_ID])) {
            $tag->invalidate();

            return false;
        }

        $group = $mentions['groups']->where('id', $id)->first();

        if ($group) {
            $tag->setAttribute('id', $group->id);
            $tag->setAttribute('groupname', $group->name_plural);

            return true;
        }

        $tag->invalidate();
    }

    private function configureTagMentions(Configurator $config)
    {
        $config->rendering->parameters['TAG_URL'] = $this->url->to('forum')->route('tag', ['slug' => '']);

        $tagName = 'TAGMENTION';

        $tag = $config->tags->add($tagName);
        $tag->attributes->add('tagname');
        $tag->attributes->add('slug');
        $tag->attributes->add('id')->filterChain->append('#uint');

        $tag->template = '
            <xsl:choose>
                <xsl:when test="@deleted != 1">
                    <a href="{$TAG_URL}{@slug}" data-id="{@id}">
                        <xsl:attribute name="class">
                            <xsl:choose>
                                <xsl:when test="@color != \'\'">
                                    <xsl:text>TagMention TagMention--colored</xsl:text>
                                </xsl:when>
                                <xsl:otherwise>
                                    <xsl:text>TagMention</xsl:text>
                                </xsl:otherwise>
                            </xsl:choose>
                        </xsl:attribute>
                        <xsl:attribute name="style">
                            <xsl:choose>
                                <xsl:when test="@color != \'\'">
                                    <xsl:text>--color:</xsl:text>
                                    <xsl:value-of select="@color"/>
                                </xsl:when>
                            </xsl:choose>
                        </xsl:attribute>
                        <span class="TagMention-text">
                            <xsl:if test="@icon != \'\'">
                                <i class="icon {@icon}"></i>
                            </xsl:if>
                            <xsl:value-of select="@tagname"/>
                        </span>
                    </a>
                </xsl:when>
                <xsl:otherwise>
                    <span class="TagMention TagMention--deleted" data-id="{@id}">
                        <span class="TagMention-text">
                            <xsl:value-of select="@tagname"/>
                        </span>
                    </span>
                </xsl:otherwise>
            </xsl:choose>';

        $tag->filterChain
            ->prepend([static::class, 'addTagId'])
            ->setJS('function(tag) { return flarum.extensions["flarum-mentions"].filterTagMentions(tag); }')
            ->addParameterByName('mentions');

        $tag->filterChain
            ->append([static::class, 'dummyFilter'])
            ->setJS('function(tag) { return flarum.extensions["flarum-mentions"].postFilterTagMentions(tag); }');

        $config->Preg->match(self::TAG_MENTION_WITH_SLUG_REGEX, $tagName);
    }

    /**
     * @param FormatterTag $tag
     * @param array<string, Collection> $mentions
     * @return true|void
     */
    public static function addTagId(FormatterTag $tag, array $mentions)
    {
        /** @var Tag|null $model */
        $model = $mentions['tags']->where('slug', $tag->getAttribute('slug'))->first();

        if ($model) {
            $tag->setAttribute('id', (string) $model->id);
            $tag->setAttribute('tagname', $model->name);

            return true;
        }
    }

    /**
     * Used when only an append JS filter is needed,
     * to add post tag validation attributes.
     */
    public static function dummyFilter(): bool
    {
        return true;
    }
}
