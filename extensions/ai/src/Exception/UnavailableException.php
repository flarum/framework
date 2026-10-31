<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Exception;

use Throwable;

/**
 * An AI call that wasn't made: the feature is off, nothing is configured, or a
 * token limit is used up. Catch this to handle all three alike, for example
 * to hide or disable the feature's control.
 *
 * Each also reaches API clients as a JSON:API error whose `code` says which:
 * `ai_feature_disabled` (403), `ai_not_configured` (503) or `ai_limit_reached` (429).
 */
interface UnavailableException extends Throwable
{
}
