<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Config;

use Flarum\ExtensionManager\Lang;
use RuntimeException;

/**
 * Credentials Composer uses to reach private and paid repositories.
 *
 * 🚨 WRITE-ONLY, and that is the whole design. A stored token is never returned
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
     * 🚨 `http-basic` takes a username AND password; the rest take a single
     * token. Storing a bare string where Composer expects an object makes
     * authentication fail with a message about the repository rather than about
     * the credential, which is a long way to walk for a typo.
     */
    public const KINDS = ['github-oauth', 'gitlab-token', 'bearer', 'http-basic'];

    public function __construct(private JsonFile $file)
    {
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
                        ? Lang::trans('config.detail_username', ['username' => (string) $value['username']])
                        : Lang::trans('config.detail_token_set'),
                ];
            }
        }

        usort($out, fn ($a, $b) => [$a['kind'], $a['host']] <=> [$b['kind'], $b['host']]);

        return $out;
    }

    /**
     * The Authorization header for a host, or null if there is no credential.
     *
     * 🚨 THE ONE PLACE A STORED SECRET IS READ, and it is deliberately shaped so
     * it cannot become an answer to anybody.
     *
     * It returns a finished header line, not the credential — nothing that calls
     * this can accidentally put the token in a response, a log line or an
     * exception message, because it never holds the token. `all()` remains the
     * only method any controller uses, and it still reports hosts and kinds and
     * nothing else.
     *
     * 🚨 Never log the return value of this method.
     *
     * Composer matches a credential to a request by HOSTNAME, so that is what
     * this takes. Passing a URL here finds nothing, which is the same mistake
     * that makes a hand-configured private repository 401 for no visible reason.
     */
    public function headerFor(string $host): ?string
    {
        $host = strtolower(trim($host));
        $auth = $this->file->read();

        $basic = $auth['http-basic'][$host] ?? null;

        if (is_array($basic) && isset($basic['username'], $basic['password'])) {
            return 'Authorization: Basic '.base64_encode(
                (string) $basic['username'].':'.(string) $basic['password']
            );
        }

        foreach (['bearer', 'github-oauth', 'gitlab-token'] as $kind) {
            $token = $auth[$kind][$host] ?? null;

            if (is_string($token) && $token !== '') {
                return 'Authorization: Bearer '.$token;
            }
        }

        return null;
    }

    public function set(string $kind, string $host, string $secret, ?string $username = null): void
    {
        if (! in_array($kind, self::KINDS, true)) {
            throw new RuntimeException(Lang::trans('config.unknown_credential_type', ['kinds' => implode(', ', self::KINDS)]));
        }

        $host = strtolower(trim($host));

        // A host, not a URL: Composer matches on the hostname, and pasting a full
        // URL here silently never matches anything.
        if (! preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $host)) {
            throw new RuntimeException(Lang::trans('config.not_a_hostname'));
        }

        if (trim($secret) === '') {
            throw new RuntimeException(Lang::trans('config.credential_empty'));
        }

        if ($kind === 'http-basic' && ($username === null || trim($username) === '')) {
            throw new RuntimeException(Lang::trans('config.basic_needs_username'));
        }

        $data = $this->file->read();
        $data[$kind] ??= [];

        $data[$kind][$host] = $kind === 'http-basic'
            ? ['username' => trim((string) $username), 'password' => $secret]
            : $secret;

        // 🚨 0600. auth.json holds credentials in plain text — that is Composer's
        // format, not a choice available here — so the file being unreadable by
        // anyone but the web user is the only protection it has.
        $this->file->write($data, 0600);
    }

    public function remove(string $kind, string $host): void
    {
        $data = $this->file->read();
        $host = strtolower(trim($host));

        if (! isset($data[$kind][$host])) {
            throw new RuntimeException(Lang::trans('config.no_credential'));
        }

        unset($data[$kind][$host]);

        if ($data[$kind] === []) {
            unset($data[$kind]);
        }

        $this->file->write($data, 0600);
    }
}
