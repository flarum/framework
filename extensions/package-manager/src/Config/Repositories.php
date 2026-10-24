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
    /** The kinds worth offering. Anything else is a footgun in a web form. */
    public const TYPES = ['vcs', 'composer', 'path'];

    public function __construct(
        protected TranslatorInterface $translator,
        protected JsonFile $file,
    ) {
    }

    /**
     * @return list<array{name:string,type:string,url:string,canonical:bool}>
     */
    public function all(): array
    {
        $out = [];

        foreach ((array) ($this->file->read()['repositories'] ?? []) as $key => $repo) {
            if (! is_array($repo)) {
                continue;
            }

            $out[] = [
                // Composer accepts repositories as a list or as a keyed map, and
                // the key is the name when it is a map.
                'name' => self::redact((string) ($repo['name'] ?? (is_string($key) ? $key : ($repo['url'] ?? '')))),
                'type' => (string) ($repo['type'] ?? '?'),
                // Never a credential written into the URL; see redact().
                'url' => self::redact((string) ($repo['url'] ?? '')),
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
     * A URL with any credential written into it (`https://user:token@host`,
     * or a token alone as the user) replaced by ***, for showing to anybody.
     * scp-style `git@host:path` names an account, not a secret, and is left.
     */
    public static function redact(string $url): string
    {
        return (string) preg_replace_callback(
            '#^([a-z][a-z0-9+.-]*://)([^/@]*)@#i',
            fn (array $m) => preg_match('#^https?://$#i', $m[1]) || str_contains($m[2], ':') ? $m[1].'***@' : $m[0],
            $url
        );
    }
}
