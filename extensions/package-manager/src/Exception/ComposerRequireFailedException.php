<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Exception;

class ComposerRequireFailedException extends ComposerCommandFailedException
{
    protected const INCOMPATIBLE_REGEX = '/(?:(?: +- {PACKAGE_NAME}(?: v[0-9A-z.-]+ requires|\[[^\[\]]+\] require) flarum\/core)|(?:Could not find a version of package {PACKAGE_NAME} matching your minim)|(?: +- Root composer.json requires {PACKAGE_NAME} [^,]+, found {PACKAGE_NAME}\[[^\[\]]+\]+ but it does not match your minimum-stability))/m';

    public function guessCause(): ?string
    {
        $hasMatches = preg_match(
            str_replace('{PACKAGE_NAME}', preg_quote($this->getRawPackageName(), '/'), self::INCOMPATIBLE_REGEX),
            $this->getMessage(),
            $matches
        );

        if ($hasMatches) {
            return 'extension_incompatible_with_instance';
        }

        return null;
    }
}
