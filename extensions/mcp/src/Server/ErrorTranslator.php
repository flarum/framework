<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Server;

use Flarum\Foundation\KnownError;
use Flarum\Foundation\ValidationException;
use Flarum\Http\Exception\RouteNotFoundException;
use Flarum\Mcp\Exception\ToolError;
use Flarum\Post\Exception\FloodingException;
use Flarum\User\Exception\NotAuthenticatedException;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException as IlluminateValidationException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use Tobyz\JsonApiServer\Exception\ErrorProvider;
use Tobyz\JsonApiServer\Exception\ForbiddenException;
use Tobyz\JsonApiServer\Exception\NotFoundException;
use Tobyz\JsonApiServer\Exception\ResourceNotFoundException;

/**
 * Turns the exceptions a call into the forum can raise into a message the
 * model can act on. Unknown exceptions give null: they are defects, reported
 * and answered with a generic internal error instead.
 *
 * The three families are disjoint: json-api-server's ErrorProvider, Flarum's
 * KnownError domain errors, and ValidationException, which is neither.
 */
class ErrorTranslator
{
    public function __construct(
        protected TranslatorInterface $translator,
    ) {
    }

    public function translate(Throwable $e): ?string
    {
        return match (true) {
            $e instanceof ToolError => $e->getMessage(),
            $e instanceof FloodingException => $this->trans('throttled'),
            $e instanceof NotAuthenticatedException => $this->trans('requires_sign_in'),
            $e instanceof PermissionDeniedException, $e instanceof ForbiddenException => $this->trans('forbidden'),
            // Never say whether a hidden model exists.
            $e instanceof ModelNotFoundException,
            $e instanceof NotFoundException,
            $e instanceof ResourceNotFoundException,
            $e instanceof RouteNotFoundException => $this->trans('not_found'),
            $e instanceof ValidationException => $this->fieldMessages([...$e->getAttributes(), ...$e->getRelationships()]),
            $e instanceof IlluminateValidationException => $this->fieldMessages(array_map(fn (array $messages) => implode(' ', $messages), $e->errors())),
            $e instanceof ErrorProvider => $this->jsonApiMessages($e),
            $e instanceof KnownError => $this->knownError($e),
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $messages
     */
    protected function fieldMessages(array $messages): string
    {
        $lines = [];

        foreach ($messages as $field => $message) {
            $lines[] = "`$field`: ".(is_array($message) ? implode(' ', $message) : (string) $message);
        }

        return implode("\n", $lines) ?: $this->trans('forbidden');
    }

    protected function jsonApiMessages(ErrorProvider $e): string
    {
        $lines = [];

        foreach ($e->getJsonApiErrors() as $error) {
            $text = trim((string) ($error['detail'] ?? $error['title'] ?? ''));
            $source = $error['source'] ?? [];
            $field = null;

            if (isset($source['pointer'])) {
                $segments = explode('/', trim((string) $source['pointer'], '/'));
                $field = end($segments) ?: null;
            } elseif (isset($source['parameter'])) {
                $field = trim((string) $source['parameter'], '[]');
            }

            if ($field !== null && $field !== '' && $field !== 'data') {
                $text = "`$field`: ".($text !== '' ? $text : $this->trans('invalid'));
            }

            if ($text !== '') {
                $lines[] = $text;
            }
        }

        return implode("\n", $lines) ?: $this->trans('invalid');
    }

    protected function knownError(KnownError&Throwable $e): string
    {
        $message = trim($e->getMessage());

        return $message !== '' ? $message : str_replace('_', ' ', $e->getType());
    }

    protected function trans(string $key): string
    {
        return $this->translator->trans("flarum-mcp.api.$key");
    }
}
