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

    public function useCdn(): bool
    {
        return $this->source() === self::SOURCE_CDN;
    }

    public function useKit(): bool
    {
        return $this->source() === self::SOURCE_KIT;
    }
}
