<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Exception;

use Flarum\Foundation\ErrorHandling\HandledError;

class ExceptionHandler
{
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
        $details = [];

        if ($guessedCause = $this->guessCause($e)) {
            $details['guessed_cause'] = $guessedCause;
        }

        if (! empty($e->details)) {
            $details = array_merge($details, $e->details);
        }

        return [$details];
    }

    protected function guessCause(ComposerCommandFailedException $e): ?string
    {
        return $e->guessCause();
    }
}
