<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr;

class ColumnAction
{
    public const OMIT = 'omit';
    public const NULLIFY = 'nullify';
    public const DEFAULT = 'default';

    public function __construct(private string $column, private string $action, private ?string $value = null, private ?string $extensionId = null)
    {
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function getExtensionId(): ?string
    {
        return $this->extensionId;
    }
}
