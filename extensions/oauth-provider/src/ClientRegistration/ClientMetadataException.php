<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\ClientRegistration;

use RuntimeException;

/**
 * A client's self-described metadata (metadata document or RFC 7591
 * registration request) was refused.
 */
class ClientMetadataException extends RuntimeException
{
    public function __construct(string $description, public readonly string $error = 'invalid_client_metadata')
    {
        parent::__construct($description);
    }
}
