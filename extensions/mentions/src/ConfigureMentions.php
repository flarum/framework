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
use Flarum\Post\CommentPost;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Support\Str;
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
        $this->configureGroupMentions($config);
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

        if ($post) {
            $tag->setAttribute('discussionid', (int) $post->discussion_id);
            $tag->setAttribute('number', (int) $post->number);

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
        $tag->attributes->add('class');
        $tag->attributes->add('id')->filterChain->append('#uint');

        $tag->template = '
            <xsl:choose>
                <xsl:when test="@deleted != 1">
                    <span class="GroupMention {@class}" style="background: {@color}">@<xsl:value-of select="@groupname"/><i class="icon {@icon}"></i></span>
                </xsl:when>
                <xsl:otherwise>
                    <span class="GroupMention GroupMention--deleted" style="background: {@color}">@<xsl:value-of select="@groupname"/><i class="icon {@icon}"></i></span>
                </xsl:otherwise>
            </xsl:choose>';
        $tag->filterChain->prepend([static::class, 'addGroupId'])
            ->setJS('function(tag) { return flarum.extensions["flarum-mentions"].filterGroupMentions(tag); }');

        $config->Preg->match('/\B@["|“](?<groupname>((?!"#[a-z]{0,3}[0-9]+).)+)["|”]#g(?<id>[0-9]+)\b/', $tagName);
    }

    /**
     * @param $tag
     * @return bool
     */
    public static function addGroupId($tag)
    {
        $group = Group::find($tag->getAttribute('id'));

        if (isset($group) && ! in_array($group->id, [Group::GUEST_ID, Group::MEMBER_ID])) {
            $tag->setAttribute('id', $group->id);
            $tag->setAttribute('groupname', $group->name_plural);
            $tag->setAttribute('icon', $group->icon ?? 'fas fa-at');
            $tag->setAttribute('color', $group->color);
            if (! empty($group->color)) {
                $tag->setAttribute('class', self::isDark($group->color) ? 'GroupMention--light' : 'GroupMention--dark');
            } else {
                $tag->setAttribute('class', '');
            }

            return true;
        }

        $tag->invalidate();
    }

    /**
     * The `isDark` utility converts a hex color to rgb, and then calcul a YIQ
     * value in order to get the appropriate brightness value (is it dark or is it
     * light?) See https://www.w3.org/TR/AERT/#color-contrast for references. A YIQ
     * value >= 128 is a light color.
     */
    public static function isDark(?string $hexColor): bool
    {
        if (! $hexColor) {
            return false;
        }

        $hexNumbers = Str::replace('#', '', $hexColor);
        if (Str::length($hexNumbers) === 3) {
            $hexNumbers += $hexNumbers;
        }

        $r = hexdec(Str::substr($hexNumbers, 0, 2));
        $g = hexdec(Str::subStr($hexNumbers, 2, 2));
        $b = hexdec(Str::subStr($hexNumbers, 4, 2));
        $yiq = ($r * 299 + $g * 587 + $b * 114) / 1000;

        return $yiq >= 128 ? false : true;
    }
}
