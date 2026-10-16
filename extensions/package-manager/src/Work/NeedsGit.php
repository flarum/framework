<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Work;

use Flarum\ExtensionManager\Lang;
/**
 * The package sources Composer can only read by running git — which a host
 * that disables proc_open will not let it do.
 *
 * 🚨 Asked BEFORE the resolve, from composer.json alone. Left to Composer, a
 * source like this fails halfway through the resolve as a Symfony LogicException
 * about proc_open, with a stack trace, on a screen whose owner did nothing
 * wrong. Every case below is one where the answer is known in advance.
 *
 *   - `git`, `hg`, `svn`, `fossil`, `perforce` repositories are a program by
 *     definition.
 *   - A `vcs` repository on a host Composer has no API driver for is git.
 *   - GitHub with `"no-api": true` is git.
 *   - GitHub WITHOUT a token: Composer tries the API, and a private repository
 *     answers 404, which Composer treats as "fall back to git". A public one
 *     works until the shared host's IP uses up GitHub's 60 anonymous calls an
 *     hour — then it, too, falls back to git. A token fixes both.
 *   - A `path` repository whose package declares no version: Composer asks git
 *     what branch the directory is on.
 *
 * GitLab and Bitbucket are left alone without a token: a public repository
 * there works through the API, and a private one fails with a sentence from
 * ComposerRunner::explainThrowable rather than a trace.
 */
final class NeedsGit
{
    public const WHY_TOKEN = 'token';
    public const WHY_GIT = 'git';

    public function __construct(private string $installPath, private string $composerHome)
    {
    }

    /**
     * @return list<array{url:string, why:string}>
     */
    public function blockers(): array
    {
        $json = $this->read($this->installPath.'/composer.json');
        $config = (array) ($json['config'] ?? []);
        $githubDomains = array_map('strtolower', (array) ($config['github-domains'] ?? ['github.com']));
        $gitlabDomains = array_map('strtolower', (array) ($config['gitlab-domains'] ?? ['gitlab.com']));
        $tokens = $this->githubTokenHosts($config);

        $out = [];

        foreach ((array) ($json['repositories'] ?? []) as $repo) {
            if (! is_array($repo)) {
                continue;     // `"packagist.org": false`
            }

            $type = strtolower((string) ($repo['type'] ?? ''));
            $url = (string) ($repo['url'] ?? '');
            $host = self::host($url);

            $why = match (true) {
                in_array($type, ['git', 'hg', 'svn', 'fossil', 'perforce'], true) => self::WHY_GIT,
                $type === 'path' => $this->pathNeedsGit($url, (array) ($repo['options'] ?? [])) ? self::WHY_GIT : null,
                $type === 'github' || ($type === 'vcs' && in_array($host, $githubDomains, true)) => match (true) {
                    ! empty($repo['no-api']), ($config['use-github-api'] ?? true) === false => self::WHY_GIT,
                    ! in_array($host, $tokens, true) => self::WHY_TOKEN,
                    default => null,
                },
                $type === 'vcs' => in_array($host, $gitlabDomains, true) || $host === 'bitbucket.org'
                    ? null
                    : self::WHY_GIT,
                default => null,
            };

            if ($why !== null) {
                $out[] = ['url' => $url, 'why' => $why];
            }
        }

        return $out;
    }

    /**
     * The refusal, in words that say what to do.
     *
     * @param list<array{url:string, why:string}> $blockers
     */
    public static function explain(array $blockers): string
    {
        $tokens = array_column(array_filter($blockers, fn ($b) => $b['why'] === self::WHY_TOKEN), 'url');
        $git = array_column(array_filter($blockers, fn ($b) => $b['why'] === self::WHY_GIT), 'url');

        $out = Lang::trans('git.blocked');

        if ($tokens !== []) {
            $out .= ' '.Lang::trans('git.add_token', ['urls' => implode(', ', $tokens)]);
        }

        if ($git !== []) {
            $out .= ' '.Lang::trans('git.git_only', ['urls' => implode(', ', $git)]);
        }

        return $out;
    }

    /** The host part of an https://, ssh:// or git@host:path URL, lower-cased. */
    public static function host(string $url): string
    {
        if (preg_match('#^[a-z0-9+.-]+://(?:[^@/]+@)?([^/:]+)#i', $url, $m)
            || preg_match('#^[^@/]+@([^:/]+):#', $url, $m)) {
            return strtolower($m[1]);
        }

        return '';
    }

    /**
     * Hosts with a github-oauth token anywhere Composer would look for one.
     *
     * @param array<string,mixed> $config composer.json's own config block
     * @return list<string>
     */
    private function githubTokenHosts(array $config): array
    {
        $sources = [
            (array) ($config['github-oauth'] ?? []),
            (array) ($this->read($this->installPath.'/auth.json')['github-oauth'] ?? []),
            (array) ($this->read($this->composerHome.'/auth.json')['github-oauth'] ?? []),
        ];

        $env = getenv('COMPOSER_AUTH') ?: ($_SERVER['COMPOSER_AUTH'] ?? '');
        if (is_string($env) && $env !== '') {
            $decoded = json_decode($env, true);
            $sources[] = (array) (is_array($decoded) ? ($decoded['github-oauth'] ?? []) : []);
        }

        $hosts = [];
        foreach ($sources as $source) {
            foreach ($source as $host => $token) {
                if (is_string($token) && $token !== '') {
                    $hosts[] = strtolower((string) $host);
                }
            }
        }

        return $hosts;
    }

    /** @param array<string,mixed> $options */
    private function pathNeedsGit(string $url, array $options): bool
    {
        $pattern = str_starts_with($url, '/') ? $url : $this->installPath.'/'.$url;

        foreach (glob(rtrim($pattern, '/'), GLOB_ONLYDIR) ?: [] as $dir) {
            $package = $this->read($dir.'/composer.json');

            if ($package === []) {
                continue;
            }

            if (! isset($package['version']) && ! isset($options['versions'][(string) ($package['name'] ?? '')])) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string,mixed> */
    private function read(string $path): array
    {
        $data = is_file($path) ? json_decode((string) @file_get_contents($path), true) : null;

        return is_array($data) ? $data : [];
    }
}
