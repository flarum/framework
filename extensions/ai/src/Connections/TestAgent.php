<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Connections;

use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

/**
 * Asks a connection for a short reply that comes in several pieces, so a test
 * shows both that the provider answers and that its reply streams.
 *
 * The token limit leaves room for models that reason before they write.
 *
 * @internal
 */
#[MaxTokens(256)]
class TestAgent implements Agent
{
    use Promptable;

    public const PROMPT = 'Count from 1 to 10, one number per line.';

    public function instructions(): string
    {
        return 'Answer with the numbers only, nothing else.';
    }
}
