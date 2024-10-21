<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Flarum\User\User;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface as Request;
use Tobyz\JsonApiServer\Exception\BadRequestException;

class RequestUtil
{
    public static function isApiRequest(Request $request): bool
    {
        return Str::contains(
            $request->getHeaderLine('Accept'),
            'application/vnd.api+json'
        );
    }

    public static function isHtmlRequest(Request $request): bool
    {
        return Str::contains(
            $request->getHeaderLine('Accept'),
            'text/html'
        );
    }

    public static function getActor(Request $request): User
    {
        return $request->getAttribute('actorReference')->getActor();
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
        $limit = $request->getQueryParams()['page']['limit'] ?? '';

        if (! filled($limit)) {
            $limit = $defaultLimit;
        }

        if (! $limit) {
            return null;
        }

        if ($max !== null) {
            $limit = min($limit, $max);
        }

        if ($limit < 1) {
            throw new BadRequestException('page[limit] must be at least 1');
        }

        return $limit;
    }

    public static function extractOffsetFromNumber(Request $request, int $limit): int
    {
        $page = (int) ($request->getQueryParams()['page']['number'] ?? 1);

        if ($page < 1) {
            throw new BadRequestException('page[number] must be at least 1');
        }

        return ($page - 1) * $limit;
    }

    public static function extractOffset(Request $request, ?int $limit = 0): int
    {
        if ($request->getQueryParams()['page']['number'] ?? false) {
            return self::extractOffsetFromNumber($request, $limit);
        }

        $offset = (int) ($request->getQueryParams()['page']['offset'] ?? 0);

        if ($offset < 0) {
            throw new BadRequestException('page[offset] must be at least 0');
        }

        return $offset;
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
