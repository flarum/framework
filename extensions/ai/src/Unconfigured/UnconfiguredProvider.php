<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Unconfigured;

use Laravel\Ai\Contracts\Gateway\EmbeddingGateway;
use Laravel\Ai\Contracts\Gateway\StepTextGateway;
use Laravel\Ai\Providers\OllamaProvider;

/**
 * Stands in as the default provider while no connection is configured. Calls
 * to it throw a NotConfiguredException. It has to be a real provider rather
 * than throw on creation: the SDK's fakes resolve the default provider and
 * swap in their own gateway, so tests that fake agents need no connection.
 *
 * Ollama's provider is the base only because it needs no key.
 */
class UnconfiguredProvider extends OllamaProvider
{
    public function textGateway(): StepTextGateway
    {
        return $this->textGateway ??= new UnconfiguredGateway();
    }

    public function embeddingGateway(): EmbeddingGateway
    {
        return $this->embeddingGateway ??= new UnconfiguredGateway();
    }
}
