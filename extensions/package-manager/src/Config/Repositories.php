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
 * Where Composer is allowed to look for packages.
 *
 * This is the setting that makes a forum's own extensions installable at all.
 * On the site this was built for, eleven of the twelve entries are protected
 * GitHub repositories and the twelfth is a paid marketplace — remove them and
 * nothing that forum runs can be resolved. It is not an advanced nicety, it is
 * the reason `composer require` finds anything.
 */
class Repositories
{
    /** A path segment that is a token by its shape: GitHub, GitLab, Bitbucket and Private Packagist prefixes. */
    protected const TOKEN = '#^(?:gh[opsur]_|github_pat_|gl[a-z]{2,4}-|ATBB|ATCTT|packagist_[a-z]{3}_)[A-Za-z0-9_-]{8,}$#';

    /** A path segment whose name says the next one is a secret. */
    protected const NAMES_A_SECRET = '#^(?:token|access[_-]?token|auth[_-]?token|api[_-]?key|apikey|key|secret|password|auth)$#i';

    /** The kinds worth offering. Anything else is a footgun in a web form. */
    public const TYPES = ['vcs', 'composer', 'path'];

    public function __construct(
        protected TranslatorInterface $translator,
        protected JsonFile $file,
    ) {
    }

    /**
     * For showing: every credential written into a URL or name is redacted.
     *
     * @return list<array{name:string,type:string,url:string,canonical:bool}>
     */
    public function all(): array
    {
        return array_map(
            fn (array $repo) => array_merge($repo, ['name' => self::redact($repo['name']), 'url' => self::redact($repo['url'])]),
            $this->unredacted()
        );
    }

    /**
     * For this extension's own requests only, never for a response: the URLs
     * as composer.json has them, credentials included, so a repository at
     * https://user:token@repo.example.com can be asked as Composer asks it.
     *
     * @return list<array{name:string,type:string,url:string,canonical:bool}>
     */
    public function unredacted(): array
    {
        $out = [];

        foreach ((array) ($this->file->read()['repositories'] ?? []) as $key => $repo) {
            if (! is_array($repo)) {
                continue;
            }

            $out[] = [
                // Composer accepts repositories as a list or as a keyed map, and
                // the key is the name when it is a map.
                'name' => (string) ($repo['name'] ?? (is_string($key) ? $key : ($repo['url'] ?? ''))),
                'type' => (string) ($repo['type'] ?? '?'),
                'url' => (string) ($repo['url'] ?? ''),
                'canonical' => (bool) ($repo['canonical'] ?? true),
            ];
        }

        return $out;
    }

    /**
     * @throws RefusedException when the entry would not be usable
     */
    public function add(string $type, string $url, ?string $name = null): void
    {
        if (! in_array($type, self::TYPES, true)) {
            throw new RefusedException($this->translator->trans('flarum-extension-manager.api.config.repo_type_unsupported', ['type' => $type]));
        }

        $url = trim($url);

        if ($url === '') {
            throw new RefusedException($this->translator->trans('flarum-extension-manager.api.config.repo_needs_url'));
        }

        /*
         * A `path` repository points at a directory, and everything else must
         * be a URL Composer can actually fetch. Checking them the same way would
         * either reject valid local paths or accept nonsense as a URL — and a
         * bad entry here breaks every later resolve, not just this one.
         */
        if ($type === 'path') {
            if (! str_starts_with($url, '/') && ! str_starts_with($url, './') && ! str_starts_with($url, '../')) {
                throw new RefusedException($this->translator->trans('flarum-extension-manager.api.config.repo_path_not_url'));
            }
        } elseif (! preg_match('#^(https?://|git@|ssh://|git://)#i', $url)) {
            throw new RefusedException($this->translator->trans('flarum-extension-manager.api.config.repo_bad_url'));
        }

        // Composer's own rule: refused here rather than by every later resolve.
        if (preg_match('#^(http|git)://#i', $url) && $this->secureHttp()) {
            throw new RefusedException($this->translator->trans('flarum-extension-manager.api.config.repo_insecure'));
        }

        $data = $this->file->read();
        $repos = (array) ($data['repositories'] ?? []);

        foreach ($repos as $existing) {
            if (is_array($existing) && rtrim((string) ($existing['url'] ?? ''), '/') === rtrim($url, '/')) {
                throw new RefusedException($this->translator->trans('flarum-extension-manager.api.config.repo_exists'));
            }
        }

        $entry = ['type' => $type, 'url' => $url];

        if ($name !== null && trim($name) !== '') {
            $entry['name'] = trim($name);
        }

        // Appended, keeping the existing shape: Composer resolves repositories in
        // order, so a new entry going last cannot change how anything already
        // installed is found.
        $repos[] = $entry;
        $data['repositories'] = array_values($repos);

        $this->file->write($data);
    }

    /**
     * Remove by URL, which is the only field guaranteed to be there. The admin
     * screen only ever has the redacted form, so that is what is compared.
     */
    public function remove(string $url): void
    {
        $data = $this->file->read();
        $repos = (array) ($data['repositories'] ?? []);
        $keep = [];
        $found = false;

        foreach ($repos as $repo) {
            if (is_array($repo) && rtrim(self::redact((string) ($repo['url'] ?? '')), '/') === rtrim(self::redact(trim($url)), '/')) {
                $found = true;
                continue;
            }

            $keep[] = $repo;
        }

        if (! $found) {
            throw new RefusedException($this->translator->trans('flarum-extension-manager.api.config.repo_missing'));
        }

        $data['repositories'] = $keep;
        $this->file->write($data);
    }

    /**
     * Composer's secure-http, as this site's composer.json sets it, read the
     * way Composer reads it: on unless turned off, and off when disable-tls is
     * on. While it is on, Composer refuses http://, git://, ftp:// and svn://.
     */
    public function secureHttp(): bool
    {
        $config = (array) ($this->file->read()['config'] ?? []);
        $on = fn (mixed $value): bool => $value !== 'false' && (bool) $value;

        return ! $on($config['disable-tls'] ?? false) && $on($config['secure-http'] ?? true);
    }

    /**
     * A URL with any credential written into it replaced by ***, for showing
     * to anybody: the user part (`https://user:token@host`, or a token alone
     * as the user), every query value (`?token=`, `?access_token=`, and any
     * other, since a query is where hosts put a token when it is not in the
     * user part), and a path segment that is recognizably a token (a GitHub,
     * GitLab, Bitbucket or Private Packagist token, or whatever follows a
     * segment named token, key, secret or similar). scp-style `git@host:path`
     * names an account, not a secret, and is left.
     *
     * Composer's own Url::sanitize() is not enough on its own: it masks only
     * `access_token` in a query and keeps the first three characters of a
     * long user name.
     */
    public static function redact(string $url): string
    {
        if (! preg_match('#^([a-z][a-z0-9+.-]*://)([^/?\#]*)([^?\#]*)(\?[^\#]*)?(\#.*)?$#is', $url, $m)) {
            return $url;
        }

        [, $scheme, $authority, $path] = $m;
        $query = $m[4] ?? '';

        // Up to the LAST @ before the path, as parse_url() and Composer read it.
        $at = strrpos($authority, '@');

        if ($at !== false && (preg_match('#^https?://$#i', $scheme) || str_contains(substr($authority, 0, $at), ':'))) {
            $authority = '***'.substr($authority, $at);
        }

        $segments = explode('/', $path);

        foreach ($segments as $i => $segment) {
            if (preg_match(self::TOKEN, $segment) || ($i > 0 && $segment !== '' && preg_match(self::NAMES_A_SECRET, $segments[$i - 1]))) {
                $segments[$i] = '***';
            }
        }

        if ($query !== '') {
            $query = '?'.implode('&', array_map(
                fn (string $pair) => $pair === '' ? '' : (str_contains($pair, '=') ? strstr($pair, '=', true).'=***' : '***'),
                explode('&', substr($query, 1))
            ));
        }

        return $scheme.$authority.implode('/', $segments).$query.($m[5] ?? '');
    }

    /**
     * Every URL in a piece of text redacted as {@see redact()} does: for an
     * error message or a log line that quotes a URL, such as a failed
     * download.
     */
    public static function redactUrls(string $text): string
    {
        return (string) preg_replace_callback('#[a-z][a-z0-9+.-]*://[^\s"\'<>]+#i', fn (array $m) => self::redact($m[0]), $text);
    }
}
