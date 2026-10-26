<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Config;

use Flarum\ExtensionManager\Exception\RefusedException;
use Flarum\Locale\TranslatorInterface;

/**
 * Credentials Composer uses to reach protected and paid repositories.
 *
 * WRITE-ONLY, and that is the whole design. A stored token is never returned
 * to the browser by any method on this class — `all()` reports which hosts have
 * a credential and nothing else. A settings screen that displays your GitHub
 * token back to you is worse than no screen: it turns every admin session, every
 * screenshot and every support ticket with a screenshot in it into a credential
 * leak, and the person it leaks from has no reason to think anything happened.
 *
 * Setting a value replaces it. Removing deletes it. Reading it back is not
 * offered, because nothing in this extension needs to read it — Composer reads
 * auth.json for itself.
 */
class AuthTokens
{
    /**
     * The kinds Composer understands, and what each expects.
     *
     * `http-basic` takes a username AND password; the rest take a single
     * token. Storing a bare string where Composer expects an object makes
     * authentication fail with a message about the repository rather than about
     * the credential, which is a long way to walk for a typo.
     */
    public const KINDS = ['github-oauth', 'gitlab-token', 'bearer', 'http-basic'];

    public function __construct(
        protected TranslatorInterface $translator,
        protected JsonFile $file,
    ) {
    }

    /**
     * Which hosts have a credential — never the credentials.
     *
     * @return list<array{kind:string,host:string,detail:string}>
     */
    public function all(): array
    {
        $out = [];

        foreach ($this->file->read() as $kind => $entries) {
            if (! is_array($entries)) {
                continue;
            }

            foreach ($entries as $host => $value) {
                $out[] = [
                    'kind' => (string) $kind,
                    'host' => (string) $host,
                    /*
                     * For http-basic the USERNAME is not the secret and is worth
                     * showing — it is how somebody tells two accounts on one host
                     * apart. The password is never included.
                     */
                    'detail' => is_array($value) && isset($value['username'])
                        ? $this->translator->trans('flarum-extension-manager.api.config.detail_username', ['username' => (string) $value['username']])
                        : $this->translator->trans('flarum-extension-manager.api.config.detail_token_set'),
                ];
            }
        }

        usort($out, fn ($a, $b) => [$a['kind'], $a['host']] <=> [$b['kind'], $b['host']]);

        return $out;
    }

    /**
     * The Authorization header value for a request to $url, or null.
     *
     * The one place a stored secret is read, and it is shaped so it cannot
     * become an answer to anybody: it returns a finished header value, never
     * the credential, and `all()` remains the only method any controller uses.
     * Never log the return value of this method.
     */
    public function authorizationFor(string $url): ?string
    {
        return self::authorization($this->file->read(), $url);
    }

    /**
     * The credential auth.json holds for exactly this URL's host, as Composer
     * chooses it: by host and port, never for a subdomain, and only over
     * HTTPS. The one alias is Composer's own: a github.com token is for
     * GitHub's API, api.github.com.
     *
     * @param array<string,mixed> $auth auth.json
     */
    public static function authorization(array $auth, string $url): ?string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $port = parse_url($url, PHP_URL_PORT);
        $origin = $port ? $host.':'.$port : $host;

        if ($host === '' || strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https') {
            return null;
        }

        $basic = $auth['http-basic'][$origin] ?? null;

        if (is_array($basic) && isset($basic['username'], $basic['password'])) {
            return 'Basic '.base64_encode((string) $basic['username'].':'.(string) $basic['password']);
        }

        $github = $auth['github-oauth'][$origin] ?? ($origin === 'api.github.com' ? ($auth['github-oauth']['github.com'] ?? null) : null);

        foreach ([$auth['bearer'][$origin] ?? null, $github, $auth['gitlab-token'][$origin] ?? null] as $token) {
            if (is_string($token) && $token !== '') {
                return 'Bearer '.$token;
            }
        }

        return null;
    }

    public function set(string $kind, string $host, string $secret, ?string $username = null): void
    {
        if (! in_array($kind, self::KINDS, true)) {
            throw new RefusedException($this->translator->trans('flarum-extension-manager.api.config.unknown_credential_type', ['kinds' => implode(', ', self::KINDS)]));
        }

        $host = strtolower(trim($host));

        // A host, not a URL: Composer matches on the hostname, and pasting a full
        // URL here silently never matches anything.
        if (! preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $host)) {
            throw new RefusedException($this->translator->trans('flarum-extension-manager.api.config.not_a_hostname'));
        }

        if (trim($secret) === '') {
            throw new RefusedException($this->translator->trans('flarum-extension-manager.api.config.credential_empty'));
        }

        if ($kind === 'http-basic' && ($username === null || trim($username) === '')) {
            throw new RefusedException($this->translator->trans('flarum-extension-manager.api.config.basic_needs_username'));
        }

        $data = $this->file->read();
        $data[$kind] ??= [];

        $data[$kind][$host] = $kind === 'http-basic'
            ? ['username' => trim((string) $username), 'password' => $secret]
            : $secret;

        // 0600, and no backup copy. auth.json holds credentials in plain text
        // (Composer's format, not a choice available here), so the file being
        // unreadable by anyone but the web user is the only protection it has,
        // and a second copy would be one more place to leak from. The write is
        // atomic and refuses invalid JSON, which is what a backup guards against.
        $this->file->write($data, 0600, false);
    }

    public function remove(string $kind, string $host): void
    {
        $data = $this->file->read();
        $host = strtolower(trim($host));

        if (! isset($data[$kind][$host])) {
            throw new RefusedException($this->translator->trans('flarum-extension-manager.api.config.no_credential'));
        }

        unset($data[$kind][$host]);

        if ($data[$kind] === []) {
            unset($data[$kind]);
        }

        $this->file->write($data, 0600, false);
    }
}
