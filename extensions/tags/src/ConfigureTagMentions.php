<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags;

use Flarum\Extension\ExtensionManager;
use Flarum\Http\UrlGenerator;
use Illuminate\Support\Str;
use s9e\TextFormatter\Configurator;

class configureTagMentions
{
    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * @param UrlGenerator $url
     * @param ExtensionManager $extensions
     */
    public function __construct(UrlGenerator $url, ExtensionManager $extensions)
    {
        $this->url = $url;
        $this->extensions = $extensions;
    }

    public function __invoke(Configurator $config)
    {
        if ($this->extensions->isEnabled('flarum-mentions')) {
            $this->configureTagMentions($config);
        }
    }

    private function configureTagMentions(Configurator $config)
    {
        $config->rendering->parameters['TAG_URL'] = $this->url->to('forum')->route('tag', ['slug' => '']);

        $tagName = 'TAGMENTION';

        $tag = $config->tags->add($tagName);
        $tag->attributes->add('tagname');
        $tag->attributes->add('icon');
        $tag->attributes->add('color');
        $tag->attributes->add('class');
        $tag->attributes->add('slug');
        $tag->attributes->add('id')->filterChain->append('#uint');

        $tag->template = '
            <xsl:choose>
                <xsl:when test="@deleted != 1">
                    <a href="{$TAG_URL}{@slug}" class="TagMention {@class}" style="background: {@color}"><i class="icon {@icon}"></i><xsl:value-of select="@tagname"/></a>
                </xsl:when>
                <xsl:otherwise>
                    <span class="TagMention TagMention--deleted" style="background: {@color}"><i class="icon {@icon}"></i><xsl:value-of select="@tagname"/></span>
                </xsl:otherwise>
            </xsl:choose>';
        $tag->filterChain->prepend([static::class, 'addTagId'])
            ->setJS('function(tag) { return flarum.extensions["flarum-tags"].filterTagMentions(tag); }');

        $config->Preg->match('/\B@["|“](?<tagname>((?!"#[a-z]{0,3}[0-9]+).)+)["|”]#t(?<id>[0-9]+)\b/', $tagName);
    }

    /**
     * @param $tag
     * @return bool
     */
    public static function addTagId($tag)
    {
        $tagModel = Tag::find($tag->getAttribute('id'));

        if (isset($tagModel)) {
            $tag->setAttribute('id', $tagModel->id);
            $tag->setAttribute('tagname', $tagModel->name);
            $tag->setAttribute('icon', $tagModel->icon ?? 'fas fa-tags');
            $tag->setAttribute('color', $tagModel->color);
            $tag->setAttribute('slug', $tagModel->slug);
            if (! empty($tagModel->color)) {
                $tag->setAttribute('class', self::isDark($tagModel->color) ? 'TagMention--light' : 'TagMention--dark');
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
