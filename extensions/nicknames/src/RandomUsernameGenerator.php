<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Nicknames;

use Flarum\User\User;

class RandomUsernameGenerator
{
    /**
     * Maximum number of attempts to generate a unique username.
     */
    protected const MAX_ATTEMPTS = 10;

    /**
     * Generate a random username that satisfies Flarum's username validation.
     *
     * Flarum requires usernames to match: /^(?![0-9]*$)[a-z0-9_-]+$/i
     * - At least one letter (ensured by 'user_' prefix)
     * - Only letters, numbers, dashes, and underscores
     * - Between 3-30 characters
     *
     * Format: user_{8 random hex characters}
     * Example: user_a3f7b2c9
     *
     * @return string A unique random username
     * @throws \RuntimeException If unable to generate a unique username after MAX_ATTEMPTS
     */
    public function generate(): string
    {
        $attempts = 0;

        do {
            $username = $this->generateCandidate();
            $attempts++;

            // Check if username is unique
            if (! User::where('username', $username)->exists()) {
                return $username;
            }
        } while ($attempts < self::MAX_ATTEMPTS);

        throw new \RuntimeException(
            'Unable to generate a unique random username after '.self::MAX_ATTEMPTS.' attempts'
        );
    }

    /**
     * Generate a single random username candidate.
     *
     * @return string A random username in format: user_{hex}
     */
    protected function generateCandidate(): string
    {
        // Generate 4 random bytes = 8 hex characters
        // This provides 4.3 billion possible combinations
        $randomHex = bin2hex(random_bytes(4));

        return 'user_'.$randomHex;
    }
}
