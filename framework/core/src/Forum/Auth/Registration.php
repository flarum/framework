<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Auth;

class Registration
{
    protected array $provided = [];
    protected array $suggested = [];
    protected mixed $payload = null;

    public function getProvided(): array
    {
        return $this->provided;
    }

    public function getSuggested(): array
    {
        return $this->suggested;
    }

    public function getPayload(): mixed
    {
        return $this->payload;
    }

    public function provide(string $key, mixed $value): self
    {
        $this->provided[$key] = $value;

        return $this;
    }

    public function provideTrustedEmail(string $email): self
    {
        return $this->provide('email', $email);
    }

    public function provideAvatar(string $url): self
    {
        return $this->provide('avatar_url', $url);
    }

    /**
     * Provide a 2× (200px) HiDPI avatar URL from the OAuth provider.
     *
     * Call this alongside provideAvatar() when the provider's CDN supports
     * sized images (e.g. GitHub's ?s=200). Flarum will fetch and store this
     * URL directly rather than upscaling the base avatar.
     */
    public function provideAvatar2x(string $url): self
    {
        return $this->provide('avatar_url_2x', $url);
    }

    /**
     * Provide a 3× (300px) HiDPI avatar URL from the OAuth provider.
     *
     * Call this alongside provideAvatar() when the provider's CDN supports
     * sized images (e.g. GitHub's ?s=300). Flarum will fetch and store this
     * URL directly rather than upscaling the base avatar.
     */
    public function provideAvatar3x(string $url): self
    {
        return $this->provide('avatar_url_3x', $url);
    }

    public function suggest(string $key, mixed $value): self
    {
        $this->suggested[$key] = $value;

        return $this;
    }

    public function suggestUsername(string $username): self
    {
        $username = preg_replace('/[^a-z0-9-_]/i', '', $username);

        return $this->suggest('username', $username);
    }

    public function suggestEmail(string $email): self
    {
        return $this->suggest('email', $email);
    }

    public function setPayload(mixed $payload): self
    {
        $this->payload = $payload;

        return $this;
    }
}
