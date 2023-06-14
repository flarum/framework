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
    protected mixed $payload;

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

    public function setPayload($payload): self
    {
        $this->payload = $payload;

        return $this;
    }
}
