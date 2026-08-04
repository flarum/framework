<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Flarum\Settings\SettingsRepositoryInterface;

class FontAwesome
{
    public const SOURCE_LOCAL = 'local';
    public const SOURCE_CDN = 'cdn';
    public const SOURCE_KIT = 'kit';

    public function __construct(
        protected readonly Config $config,
        protected readonly SettingsRepositoryInterface $settings
    ) {
    }

    public function source(): string
    {
        $source = $this->config->fontawesomeSource();

        if ($source === null) {
            $source = strval($this->settings->get('fontawesome_source', self::SOURCE_LOCAL));
        }

        return $source;
    }

    public function cdnUrl(): ?string
    {
        $url = $this->config->fontawesomeCdnUrl();

        if ($url === null) {
            $url = $this->settings->get('fontawesome_cdn_url');
        }

        return $url ?: null;
    }

    public function kitUrl(): ?string
    {
        $url = $this->config->fontawesomeKitUrl();

        if ($url === null) {
            $url = $this->settings->get('fontawesome_kit_url');
        }

        return $url ?: null;
    }

    public function configOverride(): bool
    {
        return $this->config->fontawesomeSource() !== null;
    }

    public function useLocalFonts(): bool
    {
        return $this->source() === self::SOURCE_LOCAL;
    }

    /**
     * Whether the bundled font files are what the icons will actually be drawn
     * from.
     *
     * This is not quite the same question as `useLocalFonts()`. A forum can name
     * a CDN or a Kit and give no URL for it, in which case that source cannot
     * deliver anything and the bundled fonts are all there is. Treating such a
     * forum as remote would leave it with no icons at all, rather than with
     * icons that arrive late.
     */
    public function needsBundledFonts(): bool
    {
        if ($this->useLocalFonts()) {
            return true;
        }

        if ($this->useCdn()) {
            return $this->cdnUrl() === null;
        }

        if ($this->useKit()) {
            return $this->kitUrl() === null;
        }

        // An unrecognised source names nothing we can load, so the bundled
        // fonts remain the only ones available.
        return true;
    }

    public function useCdn(): bool
    {
        return $this->source() === self::SOURCE_CDN;
    }

    public function useKit(): bool
    {
        return $this->source() === self::SOURCE_KIT;
    }
}
