<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Exception;

use Flarum\Foundation\ErrorHandling\HandledError;

class ComposerCommandFailedExceptionHandler
{
    protected const INCOMPATIBLE_REGEX = '/(?:(?: +- {PACKAGE_NAME}(?: v[0-9A-z.-]+ requires|\[[^\[\]]+\] require) flarum\/core)|(?:Could not find a version of package {PACKAGE_NAME} matching your minim)|(?: +- Root composer.json requires {PACKAGE_NAME} [^,]+, found {PACKAGE_NAME}\[[^\[\]]+\]+ but it does not match your minimum-stability))/m';

    public function handle(ComposerCommandFailedException $e): HandledError
    {
        return (new HandledError(
            $e,
            'composer_command_failure',
            409
        ))->withDetails($this->errorDetails($e));
    }

    protected function errorDetails(ComposerCommandFailedException $e): array
    {
        $details = [
            'output' => $e->getMessage(),
        ];

        if ($guessedCause = $this->guessCause($e)) {
            $details['guessed_cause'] = $guessedCause;
        }

        return [$details];
    }

    protected function guessCause(ComposerCommandFailedException $e): ?string
    {
        $rawPackageName = preg_replace('/^([A-z0-9-_\/]+)(?::.*|)$/i', '$1', $e->packageName);

        if ($e instanceof ComposerRequireFailedException) {
            $hasMatches = preg_match(str_replace('{PACKAGE_NAME}', preg_quote($rawPackageName, '/'), self::INCOMPATIBLE_REGEX), $e->getMessage(), $matches);

            if ($hasMatches) {
                return 'extension_incompatible_with_instance';
            }
        }

        return null;
    }
}
