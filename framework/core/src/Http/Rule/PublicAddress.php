<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Rule;

use Closure;
use Flarum\Http\OutboundAddressResolver;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The host of the URL under validation must resolve to an address Flarum may
 * send a server-side request to (see OutboundAddressResolver: public or
 * private LAN, never loopback, link-local or reserved).
 *
 * Combine it with the `url` rule: a value without a host fails here too.
 */
class PublicAddress implements ValidationRule
{
    public function __construct(
        protected OutboundAddressResolver $resolver
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $host = is_string($value) ? parse_url($value, PHP_URL_HOST) : null;

        if (! is_string($host) || $host === '' || $this->resolver->resolve($host) === null) {
            $fail('validation.public_address')->translate();
        }
    }
}
