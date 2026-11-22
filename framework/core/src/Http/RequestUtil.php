<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Bitworking\Mimeparse;
use Flarum\Post\PostSource;
use Flarum\User\User;
use Psr\Http\Message\ServerRequestInterface as Request;
use Tobyz\JsonApiServer\Exception\BadRequestException;

class RequestUtil
{
    /**
     * Determine if the request is an API request. We do not manually set the `Accepts` header
     * for API requests, so we need to check the priority list to determine whether it is or not.
     * Normal browsing requests will have `text/html` as their preferred content type, while API will have `* / *`.
     */
    public static function isApiRequest(Request $request): bool
    {
        $preferred = self::getPreferredContentType(
            $request,
            ['application/vnd.api+json', 'application/json', 'text/html']
        );

        return in_array($preferred, ['application/vnd.api+json', 'application/json'], true);
    }

    public static function isHtmlRequest(Request $request): bool
    {
        return ! self::isApiRequest($request);
    }

    /**
     * Attribute key marking a request as an internal API call (dispatched via
     * {@see \Flarum\Api\Client}, e.g. when a frontend page preloads its API
     * document) rather than a request from an external client.
     */
    public const INTERNAL_ATTRIBUTE = 'flarum.api.internal';

    public static function withInternal(Request $request): Request
    {
        return $request->withAttribute(self::INTERNAL_ATTRIBUTE, true);
    }

    /**
     * Whether this request was dispatched internally. Internal requests are not
     * held to the JSON:API spec's strict query-parameter validation: a frontend
     * page forwards the browser's query string (which may carry tracking params
     * like `fbclid`) into the API client, and those must not 400 the page.
     */
    public static function isInternal(Request $request): bool
    {
        return (bool) $request->getAttribute(self::INTERNAL_ATTRIBUTE, false);
    }

    /**
     * Determine the client's preferred content type out of the given list.
     *
     * @param Request $request
     * @param array $types The content types to check against, in order of priority. The best match will be returned.
     * @return string The best matching type, or an empty string if none could be determined.
     */
    public static function getPreferredContentType(Request $request, array $types): string
    {
        $accept = $request->getHeaderLine('Accept');

        // We get some errors if $accept is empty, so we need to check for that and set it to */* if it is.
        if (! filled($accept)) {
            $accept = '*/*';
        }

        // This is called from the error handler, so it must never throw or emit noise.
        // `bestMatch()` returns null when nothing matches, and on a malformed Accept
        // header (e.g. a media range without a subtype) it emits a warning and then
        // throws. The `@` swallows that warning and the catch swallows the exception;
        // in either case we fall back to an empty string, which callers treat as
        // "no preference" (i.e. not an API request).
        try {
            return @Mimeparse::bestMatch($types, $accept) ?? '';
        } catch (\UnexpectedValueException) {
            return '';
        }
    }

    public static function getActor(Request $request): User
    {
        return $request->getAttribute('actorReference')->getActor();
    }

    /**
     * The class the forum puts on `<html>` to tell guests from signed-in users:
     * `guest-user` or `logged-in`. Standalone pages that use `layouts/basic` pass
     * it to the view as `actorClass`, so a theme can rely on it across them.
     *
     * It reflects the actor as far as the request got. The reference is shared and
     * filled in by the authentication middleware, so an error thrown before that
     * has run (for example in `StartSession`, `RememberFromCookie`, `CollectGarbage`
     * or an extension's middleware) still sees the `Guest` that
     * `InjectActorReference` set first, and a signed-in visitor gets `guest-user`.
     * That errs on the safe side.
     *
     * Returns null when the request has no actor reference at all.
     */
    public static function getActorHtmlClass(Request $request): ?string
    {
        if (! $request->getAttribute('actorReference')) {
            return null;
        }

        return self::getActor($request)->isGuest() ? 'guest-user' : 'logged-in';
    }

    public static function withActor(Request $request, User $actor): Request
    {
        $actorReference = $request->getAttribute('actorReference');

        if (! $actorReference) {
            $actorReference = new ActorReference;
            $request = $request->withAttribute('actorReference', $actorReference);
        }

        $actorReference->setActor($actor);

        return $request;
    }

    /**
     * Attribute key holding the {@see PostSource} of the request's posts.
     */
    public const POST_SOURCE_ATTRIBUTE = 'flarum.postSource';

    /**
     * Record how this request reached the forum, so posts it creates show it.
     * Pass null for a request from the forum's own interface.
     */
    public static function withPostSource(Request $request, ?PostSource $source): Request
    {
        return $source
            ? $request->withAttribute(self::POST_SOURCE_ATTRIBUTE, $source)
            : $request->withoutAttribute(self::POST_SOURCE_ATTRIBUTE);
    }

    public static function getPostSource(Request $request): ?PostSource
    {
        $source = $request->getAttribute(self::POST_SOURCE_ATTRIBUTE);

        return $source instanceof PostSource ? $source : null;
    }

    public static function extractSort(Request $request, ?string $default, array $available = []): ?array
    {
        $input = $request->getQueryParams()['sort'] ?? null;

        if (is_null($input) || ! filled($input)) {
            $input = $default;
        }

        if (! $input) {
            return null;
        }

        if (! is_string($input)) {
            throw new BadRequestException('sort must be a string');
        }

        $sort = [];

        foreach (explode(',', $input) as $field) {
            if (str_starts_with($field, '-')) {
                $field = substr($field, 1);
                $order = 'desc';
            } else {
                $order = 'asc';
            }

            $sort[$field] = $order;
        }

        $invalid = array_diff(array_keys($sort), $available);

        if (count($invalid)) {
            throw new BadRequestException(
                'Invalid sort fields ['.implode(',', $invalid).']',
            );
        }

        return $sort;
    }

    public static function extractLimit(Request $request, ?int $defaultLimit = null, ?int $max = null): ?int
    {
        // A limit too large to read is still larger than the largest page.
        $limit = self::pageParam($request, 'limit', 'a positive integer', $max) ?? $defaultLimit;

        if ($limit === null) {
            return null;
        }

        if ($max !== null) {
            $limit = min($limit, $max);
        }

        // An explicit page[limit]=0 is refused rather than read as "no limit".
        if ($limit < 1) {
            throw (new BadRequestException('page[limit] must be a positive integer'))->setSource([
                'parameter' => 'page[limit]',
            ]);
        }

        return $limit;
    }

    public static function extractOffsetFromNumber(Request $request, ?int $limit): int
    {
        $page = self::pageParam($request, 'number', 'a positive integer') ?? 1;

        if ($page < 1) {
            throw (new BadRequestException('page[number] must be a positive integer'))->setSource([
                'parameter' => 'page[number]',
            ]);
        }

        // Without a limit there is only one page.
        if (! $limit) {
            return 0;
        }

        if ($page - 1 > intdiv(PHP_INT_MAX, $limit)) {
            throw (new BadRequestException('page[number] is too large'))->setSource([
                'parameter' => 'page[number]',
            ]);
        }

        return ($page - 1) * $limit;
    }

    public static function extractOffset(Request $request, ?int $limit = 0): int
    {
        // Read both, so a malformed offset is refused even when page[number] wins.
        $offset = self::pageParam($request, 'offset', 'a non-negative integer') ?? 0;

        if (self::pageParam($request, 'number', 'a positive integer') !== null) {
            return self::extractOffsetFromNumber($request, $limit);
        }

        return $offset;
    }

    /**
     * A page parameter is a whole number, not a list or a string such as 2x.
     * An empty value counts as absent.
     *
     * @param int|null $ceiling what a number too large for an integer reads as;
     *                          without one, it's refused
     */
    private static function pageParam(Request $request, string $name, string $expected, ?int $ceiling = null): ?int
    {
        $page = $request->getQueryParams()['page'] ?? null;
        $value = is_array($page) ? ($page[$name] ?? null) : null;

        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) && $value >= 0) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            $digits = ltrim($value, '0') ?: '0';

            // Casting saturates at PHP_INT_MAX instead of failing.
            if ((string) (int) $digits === $digits) {
                return (int) $digits;
            }

            if ($ceiling !== null) {
                return $ceiling;
            }
        }

        throw (new BadRequestException("page[$name] must be $expected"))->setSource([
            'parameter' => "page[$name]",
        ]);
    }

    public static function extractInclude(Request $request, ?array $available): array
    {
        $include = $request->getQueryParams()['include'] ?? '';

        if (! is_string($include)) {
            throw new BadRequestException('include must be a string');
        }

        $includes = array_filter(explode(',', $include));

        $invalid = array_diff($includes, $available);

        if (count($invalid)) {
            throw new BadRequestException('Invalid includes ['.implode(',', $invalid).']');
        }

        return $includes;
    }

    public static function extractFilter(Request $request): array
    {
        $filter = $request->getQueryParams()['filter'] ?? [];

        if (! is_array($filter)) {
            throw new BadRequestException('filter must be an array');
        }

        if (is_array($filter['q'] ?? null)) {
            throw (new BadRequestException('filter[q] must be a string'))->setSource(['parameter' => 'filter[q]']);
        }

        return $filter;
    }

    public static function extractFields(Request $request, ?array $available = null): array
    {
        $fields = $request->getQueryParams()['fields'] ?? [];

        if (! is_array($fields)) {
            throw new BadRequestException('fields must be an array');
        }

        if ($available !== null) {
            $invalid = array_diff(array_keys($fields), $available);

            if (count($invalid)) {
                throw new BadRequestException('Invalid fields ['.implode(',', $invalid).']');
            }
        }

        return $fields;
    }
}
