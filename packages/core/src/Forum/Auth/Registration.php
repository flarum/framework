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
    /**
     * @var array
     */
    protected $provided = [];

    /**
     * @var array
     */
    protected $suggested = [];

    /**
     * @var mixed
     */
    protected $payload;

    /**
     * @return array
     */
    public function getProvided(): array
    {
        return $this->provided;
    }

    /**
     * @return array
     */
    public function getSuggested(): array
    {
        return $this->suggested;
    }

    /**
     * @return mixed
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function provide(string $key, $value): self
    {
        $this->provided[$key] = $value;

        return $this;
    }

    /**
     * @param string $email
     * @return $this
     */
    public function provideTrustedEmail(string $email): self
    {
        return $this->provide('email', $email);
    }

    /**
     * @param string $url
     * @return $this
     */
    public function provideAvatar(string $url): self
    {
        return $this->provide('avatar_url', $url);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function suggest(string $key, $value): self
    {
        $this->suggested[$key] = $value;

        return $this;
    }

    /**
     * @param string $username
     * @return $this
     */
    public function suggestUsername(string $username): self
    {
        $username = preg_replace('/[^a-z0-9-_]/i', '', $username);

        return $this->suggest('username', $username);
    }

    /**
     * @param string $email
     * @return $this
     */
    public function suggestEmail(string $email): self
    {
        return $this->suggest('email', $email);
    }

    /**
     * @param mixed $payload
     * @return $this
     */
    public function setPayload($payload): self
    {
        $this->payload = $payload;

        return $this;
    }
}
