<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Plan;

use Flarum\Locale\TranslatorInterface;
use InvalidArgumentException;

/**
 * One package's worth of change, as decided by the plan phase.
 *
 * A plan is nothing more than a list of these. Keeping it that small is the
 * point: the applier never resolves anything, never asks Composer a question,
 * and never decides what should happen. It carries out a list it was handed, and
 * writes down what it did. That is what makes it testable without a network, a
 * Composer install, or a booted Flarum — and what makes an interruption
 * recoverable, because the record and the intention have the same shape.
 */
class Change
{
    public const REPLACE = 'replace';
    public const ADD = 'add';
    public const REMOVE = 'remove';

    public function __construct(
        public readonly string $op,
        public readonly string $package,
        public readonly ?string $from = null,
        public readonly ?string $to = null,
        /*
         * The commit each side points at, for a development version only
         * (`2.x-dev`, `dev-main`). A branch that moves keeps its version string,
         * so without these an update along a branch would be a change from
         * `2.x-dev` to `2.x-dev`: invisible to the plan, and two rollback copies
         * fighting over one name in the trash.
         */
        public readonly ?string $fromRef = null,
        public readonly ?string $toRef = null,
    ) {
        if (! in_array($op, [self::REPLACE, self::ADD, self::REMOVE], true)) {
            throw new InvalidArgumentException("Unknown change operation: $op");
        }

        if ($package === '' || ! str_contains($package, '/')) {
            throw new InvalidArgumentException("Not a package name: $package");
        }

        // The applier trusts these, so they are checked once, here, rather than
        // defensively at every use.
        if ($op !== self::REMOVE && $to === null) {
            throw new InvalidArgumentException("A $op of $package needs a target version");
        }

        if ($op !== self::ADD && $from === null) {
            throw new InvalidArgumentException("A $op of $package needs the version it is replacing");
        }
    }

    /**
     * The path this package occupies inside a vendor tree.
     *
     * Derived, never taken from input. A package name reaching the filesystem
     * is exactly where a traversal would get in, and the constructor's check for
     * a single slash is not enough on its own — "a/../../etc" has one slash.
     */
    public function relativePath(): string
    {
        $parts = explode('/', $this->package);

        foreach ($parts as $part) {
            if ($part === '' || $part === '.' || $part === '..' || ! preg_match('/^[A-Za-z0-9._-]+$/', $part)) {
                throw new InvalidArgumentException("Refusing to build a path from: {$this->package}");
            }
        }

        return implode(DIRECTORY_SEPARATOR, $parts);
    }

    /** A stable, filesystem-safe name for this package's slot in the trash. */
    public function trashName(): string
    {
        // A branch version can hold a slash too (dev-feature/x), and the name
        // has to stay one entry directly inside the trash.
        return str_replace('/', '+', $this->package.'@'.($this->from ?? 'absent')).$this->refSuffix();
    }

    /**
     * Which commit of a development version was set aside, so the copy kept
     * from one nightly is never mistaken for, or replaced by, the next one's.
     * Empty for everything else, so trash names written before this existed
     * still match.
     */
    protected function refSuffix(): string
    {
        $ref = preg_replace('/[^A-Za-z0-9]/', '', (string) $this->fromRef);

        return $ref === '' ? '' : '#'.substr($ref, 0, 12);
    }

    /** A version Composer takes from a branch rather than a tag. */
    public static function isDev(string $version): bool
    {
        return str_starts_with($version, 'dev-') || str_ends_with($version, '-dev');
    }

    public function describe(TranslatorInterface $translator): string
    {
        return match ($this->op) {
            self::REPLACE => $this->from === $this->to && $this->fromRef !== null && $this->toRef !== null
                ? "{$this->package} {$this->from} (".substr($this->fromRef, 0, 7).' → '.substr($this->toRef, 0, 7).')'
                : "{$this->package} {$this->from} → {$this->to}",
            self::ADD => $translator->trans('flarum-extension-manager.api.change.added', ['package' => $this->package, 'version' => $this->to]),
            self::REMOVE => $translator->trans('flarum-extension-manager.api.change.removed', ['package' => $this->package, 'version' => $this->from]),
            default => $this->package,
        };
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return array_filter([
            'op' => $this->op,
            'package' => $this->package,
            'from' => $this->from,
            'to' => $this->to,
            'fromRef' => $this->fromRef,
            'toRef' => $this->toRef,
        ], fn ($v) => $v !== null);
    }

    /** @param array<string,mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self(
            (string) ($row['op'] ?? ''),
            (string) ($row['package'] ?? ''),
            isset($row['from']) ? (string) $row['from'] : null,
            isset($row['to']) ? (string) $row['to'] : null,
            isset($row['fromRef']) ? (string) $row['fromRef'] : null,
            isset($row['toRef']) ? (string) $row['toRef'] : null,
        );
    }
}
