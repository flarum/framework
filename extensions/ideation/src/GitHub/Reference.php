<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\GitHub;

/**
 * What a moderator pastes: a GitHub URL or `owner/repo#123`.
 *
 * Parsed against GitHub's own name rules, so nothing but an owner, a repo and
 * a number ever reaches the API URL.
 */
final class Reference
{
    protected const OWNER = '[A-Za-z0-9](?:[A-Za-z0-9-]{0,38})';
    protected const REPO = '[A-Za-z0-9._-]{1,100}';

    public function __construct(
        public readonly string $repo,
        /** issue (which may turn out to be a pull request), pull or milestone. */
        public readonly string $type,
        public readonly int $number,
    ) {
    }

    public static function parse(string $input): ?self
    {
        $input = trim($input);
        $o = self::OWNER;
        $r = self::REPO;

        if (preg_match("~^https?://(?:www\\.)?github\\.com/($o)/($r)/(issues|pull|milestone)/([0-9]{1,9})(?:[/?#].*)?$~", $input, $m)) {
            $type = ['issues' => 'issue', 'pull' => 'pull', 'milestone' => 'milestone'][$m[3]];

            return self::make($m[1], $m[2], $type, $m[4]);
        }

        if (preg_match("~^($o)/($r)#([0-9]{1,9})$~", $input, $m)) {
            return self::make($m[1], $m[2], 'issue', $m[3]);
        }

        return null;
    }

    protected static function make(string $owner, string $repo, string $type, string $number): ?self
    {
        $repo = preg_replace('/\.git$/', '', $repo);

        if ($repo === '.' || $repo === '..' || (int) $number < 1) {
            return null;
        }

        return new self(strtolower($owner.'/'.$repo), $type, (int) $number);
    }
}
