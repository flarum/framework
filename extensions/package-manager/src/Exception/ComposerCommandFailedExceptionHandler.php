<?php

/**
 *
 */

namespace SychO\PackageManager\Exception;

use Flarum\Foundation\ErrorHandling\HandledError;

class ComposerCommandFailedExceptionHandler
{
    protected const INCOMPATIBLE_REGEX = '/ +- {PACKAGE_NAME} v[0-9.]+ requires flarum\/core/m';

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
        if ($e instanceof ComposerRequireFailedException) {
            $hasMatches = preg_match(str_replace('{PACKAGE_NAME}', preg_quote($e->packageName, '/'), self::INCOMPATIBLE_REGEX), $e->getMessage(), $matches);

            if ($hasMatches) {
                return 'extension_incompatible_with_instance';
            }
        }

        return null;
    }
}
