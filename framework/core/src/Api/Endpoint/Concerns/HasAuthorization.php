<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Endpoint\Concerns;

use Closure;
use Flarum\Http\RequestUtil;
use Flarum\User\Exception\NotAuthenticatedException;
use Flarum\User\Exception\PermissionDeniedException;
use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Schema\Concerns\HasVisibility;

trait HasAuthorization
{
    use HasVisibility {
        isVisible as parentIsVisible;
    }

    protected bool|Closure $authenticated = false;

    protected null|string|Closure $ability = null;

    protected bool $admin = false;

    public function authenticated(bool|Closure $condition = true): self
    {
        $this->authenticated = $condition;

        return $this;
    }

    public function can(null|string|Closure $ability): self
    {
        $this->ability = $ability;

        return $this;
    }

    public function admin(bool $admin = true): self
    {
        $this->admin = $admin;

        return $this;
    }

    public function getAuthenticated(Context $context): bool
    {
        if (is_bool($this->authenticated)) {
            return $this->authenticated;
        }

        return (bool) (isset($context->model)
            ? ($this->authenticated)($context->model, $context)
            : ($this->authenticated)($context));
    }

    public function getAuthorized(Context $context): ?string
    {
        if (! is_callable($this->ability)) {
            return $this->ability;
        }

        return isset($context->model)
            ? ($this->ability)($context->model, $context)
            : ($this->ability)($context);
    }

    /**
     * @throws NotAuthenticatedException
     * @throws PermissionDeniedException
     */
    public function isVisible(Context $context): bool
    {
        $actor = RequestUtil::getActor($context->request);

        if ($this->getAuthenticated($context)) {
            $actor->assertRegistered();
        }

        if ($this->admin) {
            $actor->assertAdmin();
        }

        if ($ability = $this->getAuthorized($context)) {
            $actor->assertCan($ability, $context->model);
        }

        return $this->parentIsVisible($context);
    }
}
