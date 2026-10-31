<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Features;

use Flarum\Foundation\KnownError;
use RuntimeException;

/**
 * The feature can't be used right now: the admin hasn't turned it on, or
 * there's no connection for it to use.
 */
class FeatureUnavailableException extends RuntimeException implements KnownError
{
    /**
     * @param 'disabled'|'no_connection' $reason
     */
    public function __construct(
        public readonly string $feature,
        public readonly string $reason
    ) {
        parent::__construct(match ($reason) {
            'disabled' => "The AI feature \"$feature\" is turned off. An admin can turn it on in the AI settings.",
            'no_connection' => "The AI feature \"$feature\" has no connection to use. Add one in the AI settings.",
        });
    }

    public function getType(): string
    {
        return 'ai_feature_unavailable';
    }
}
