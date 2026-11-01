<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Exception;

use Flarum\Foundation\KnownError;
use RuntimeException;

/**
 * The admin hasn't turned the feature on. API clients get a 403 with the code
 * `ai_feature_disabled`.
 */
class FeatureDisabledException extends RuntimeException implements KnownError, UnavailableException
{
    public function __construct(
        public readonly string $feature
    ) {
        parent::__construct("The AI feature \"$feature\" is turned off. An admin can turn it on in the AI settings.");
    }

    public function getType(): string
    {
        return 'ai_feature_disabled';
    }
}
