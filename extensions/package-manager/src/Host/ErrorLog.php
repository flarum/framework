<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Host;

/**
 * What the site said when it fell over.
 *
 * 🚨 "The update broke the site" is not an answer anybody can act on. The
 * difference between that and "TypeError: ... in ramon/classifieds/extend.php
 * on line 87" is the difference between an admin filing a bug against the
 * updater and one filing it against the extension that is actually wrong.
 *
 * Flarum writes the exception to its own daily log, so the cause is already on
 * disk by the time the health check gets a 500 back. This reads the newest one
 * written since a given moment — since, so a failure from last Tuesday is never
 * offered as the explanation for today's.
 */
class ErrorLog
{
    public function __construct(private string $storagePath)
    {
    }

    /**
     * The first line of the most recent error logged at or after $since.
     *
     * Returns null when there is nothing to show, which is a real possibility:
     * a site can fail to answer because PHP itself died, and then there is
     * nothing for Flarum to have written.
     */
    public function latest(int $since): ?string
    {
        $file = $this->storagePath.'/logs/flarum-'.gmdate('Y-m-d', $since).'.log';

        foreach ([$file, $this->storagePath.'/logs/flarum-'.gmdate('Y-m-d').'.log'] as $candidate) {
            $line = $this->scan($candidate, $since);

            if ($line !== null) {
                return $line;
            }
        }

        return null;
    }

    private function scan(string $file, int $since): ?string
    {
        if (! is_readable($file)) {
            return null;
        }

        /*
         * 🚨 The tail, not the file. A busy forum's daily log is megabytes and
         * this runs while the site is DOWN — reading the whole thing into
         * memory to find the last line of it is the wrong thing to do at the
         * worst possible moment.
         */
        $handle = @fopen($file, 'rb');

        if ($handle === false) {
            return null;
        }

        $size = max(0, filesize($file) ?: 0);
        $want = min($size, 256 * 1024);
        fseek($handle, $size - $want);
        $tail = (string) fread($handle, $want ?: 1);
        fclose($handle);

        $best = null;

        foreach (explode("\n", $tail) as $line) {
            if (! preg_match('/^\[([^\]]+)\]\s+\S+\.(ERROR|CRITICAL|ALERT|EMERGENCY):\s*(.+)$/', $line, $m)) {
                continue;
            }

            $at = strtotime($m[1]);

            if ($at === false || $at < $since) {
                continue;
            }

            // Newest wins; the log is oldest-first.
            $best = trim($m[3]);
        }

        if ($best === null) {
            return null;
        }

        // The message, not the stack trace that follows it on the same line.
        $best = preg_split('/ in \/|\sStack trace:/', $best)[0];

        return mb_substr(trim($best), 0, 300);
    }
}
