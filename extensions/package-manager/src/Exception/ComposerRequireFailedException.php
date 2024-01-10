<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Exception;

class ComposerRequireFailedException extends ComposerCommandFailedException
{
    protected const INCOMPATIBLE_REGEX = '/(?:(?: +- {PACKAGE_NAME}(?: v[0-9A-z.-]+ requires|\[[^\[\]]+\] require) flarum\/core)|(?:Could not find a version of package {PACKAGE_NAME} matching your minim)|(?: +- Root composer\.json requires {PACKAGE_NAME} [^,]+, found {PACKAGE_NAME}\[[^\[\]]+\]+ but it does not match your minimum-stability))/m';
    protected const NOT_FOUND_REGEX = '/(?:(?: +- Root composer\.json requires {PACKAGE_NAME}, it could not be found in any version, there may be a typo in the package name.))/m';

    public function guessCause(): ?string
    {
        $hasIncompatibleMatches = preg_match(
            str_replace('{PACKAGE_NAME}', preg_quote($this->getRawPackageName(), '/'), self::INCOMPATIBLE_REGEX),
            $this->getMessage(),
            $matches
        );

        if ($hasIncompatibleMatches) {
            return 'extension_incompatible_with_instance';
        }

        $hasNotFoundMatches = preg_match(
            str_replace('{PACKAGE_NAME}', preg_quote($this->getRawPackageName(), '/'), self::NOT_FOUND_REGEX),
            $this->getMessage(),
            $matches
        );

        if ($hasNotFoundMatches) {
            return 'extension_not_found';
        }

        return null;
    }
}
