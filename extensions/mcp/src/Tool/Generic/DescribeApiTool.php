<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tool\Generic;

use Flarum\Mcp\Api\ApiDescriber;
use Flarum\Mcp\Api\ResourceExposure;
use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Exception\ToolError;
use Flarum\User\User;
use Symfony\Contracts\Translation\TranslatorInterface;

class DescribeApiTool extends GenericTool
{
    public function __construct(
        ResourceExposure $exposure,
        TranslatorInterface $translator,
        protected ApiDescriber $describer,
    ) {
        parent::__construct($exposure, $translator);
    }

    protected function operation(): string
    {
        return 'show';
    }

    public function name(): string
    {
        return 'describe_api';
    }

    public function title(): string
    {
        return 'Describe the forum API';
    }

    public function description(): string
    {
        return 'Without arguments: every resource type you can reach through the generic tools, with its owning extension, operations and field, filter and sort names. '
            .'With "type": the full description of that type (attribute schemas, writability, relationships, filters, sorts, paging).';
    }

    public function isAvailableFor(User $actor): bool
    {
        return true;
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'type' => [
                    'type' => 'string',
                    'enum' => array_keys($this->exposure->exposed($this->actor)),
                    'description' => 'A resource type to describe in full.',
                ],
            ],
        ];
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        $exposed = $this->exposure->exposed($context->actor);
        $type = $arguments['type'] ?? null;

        if ($type === null) {
            $types = [];

            foreach ($exposed as $exposedType => $operations) {
                $resource = $this->exposure->resource($exposedType);

                if ($resource) {
                    $types[] = $this->describer->summary($resource, $operations);
                }
            }

            return ['types' => $types];
        }

        $resource = $this->exposure->resource((string) $type);

        if (! $resource || ! isset($exposed[$type])) {
            throw new ToolError($this->translator->trans('flarum-mcp.api.not_exposed', ['operation' => 'describe', 'type' => $type]));
        }

        return $this->describer->describe($resource, $exposed[$type], $context->actor);
    }
}
