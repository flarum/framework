<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Server;

use Flarum\Forum\Branding\Branding;
use Flarum\Foundation\Application;
use Flarum\Foundation\ErrorHandling\Reporter;
use Flarum\Http\UrlGenerator;
use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\Prompt;
use Flarum\Mcp\Capability\ResolvedCapabilities;
use Flarum\Mcp\Capability\Resource;
use Flarum\Mcp\Capability\ResourceTemplate;
use Flarum\Mcp\Capability\Tool;
use Flarum\Mcp\Overview\OverviewBuilder;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Container\Container;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\StreamFactory;
use Mcp\Schema\Icon;
use Mcp\Schema\Prompt as PromptDefinition;
use Mcp\Schema\PromptArgument;
use Mcp\Schema\ResourceDefinition;
use Mcp\Schema\ResourceTemplate as ResourceTemplateDefinition;
use Mcp\Schema\Tool as ToolDefinition;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server;
use Mcp\Server\Session\Psr16SessionStore;
use Mcp\Server\Transport\Http\Middleware\CorsMiddleware;
use Mcp\Server\Transport\Http\Middleware\DnsRebindingProtectionMiddleware;
use Mcp\Server\Transport\StreamableHttpTransport;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The only place SDK types meet Flarum's capability classes. A server is built
 * per request, for that request's actor, and holds no state of its own:
 * handshake-era sessions live in Flarum's cache.
 *
 * @internal
 */
class ServerFactory
{
    /**
     * Handshake-era sessions expire after an hour of inactivity.
     */
    public const int SESSION_TTL = 3600;

    public function __construct(
        protected Container $container,
        protected Cache $cache,
        protected SettingsRepositoryInterface $settings,
        protected UrlGenerator $url,
        protected Branding $branding,
        protected OverviewBuilder $overview,
        protected ErrorTranslator $errors,
    ) {
    }

    public function make(McpContext $context, ResolvedCapabilities $capabilities): Server
    {
        $invoker = new Invoker($this->errors, $this->container->tagged(Reporter::class));

        $builder = Server::builder()
            ->setServerInfo(
                name: 'flarum',
                version: Application::VERSION,
                description: $this->settings->get('forum_description') ?: null,
                icons: $this->icons() ?: null,
                websiteUrl: $this->url->to('forum')->base(),
                title: $this->settings->get('forum_title') ?: null,
            )
            ->setInstructions($this->overview->instructions($context, $capabilities))
            // The PSR-16 store expires its own keys, so there is nothing to collect.
            ->setSession(new Psr16SessionStore($this->cache, 'mcp-session-', static::SESSION_TTL), gcProbability: 0);

        foreach ($capabilities->tools as $tool) {
            $builder->add($this->toolDefinition($tool, $context), new ToolHandler($tool, $context, $invoker));
        }

        foreach ($capabilities->resources as $resource) {
            $builder->add($this->resourceDefinition($resource), new ResourceHandler($resource, $context, $invoker));
        }

        foreach ($capabilities->resourceTemplates as $template) {
            $builder->add($this->templateDefinition($template), new ResourceTemplateHandler($template, $context, $invoker));
        }

        foreach ($capabilities->prompts as $prompt) {
            $builder->add($this->promptDefinition($prompt), new PromptHandler($prompt, $context, $invoker));
        }

        return $builder->build();
    }

    public function transport(ServerRequestInterface $request): StreamableHttpTransport
    {
        $responseFactory = new ResponseFactory();
        $streamFactory = new StreamFactory();

        $host = parse_url($this->url->to('forum')->base(), PHP_URL_HOST) ?: 'localhost';

        return new StreamableHttpTransport(
            request: $request,
            responseFactory: $responseFactory,
            streamFactory: $streamFactory,
            middleware: [
                // No cross-origin browser client is trusted: only same-origin
                // pages and non-browser clients get through.
                new CorsMiddleware(allowedOrigins: []),
                new DnsRebindingProtectionMiddleware(
                    allowedHosts: [$host],
                    allowedOrigins: [],
                    responseFactory: $responseFactory,
                    streamFactory: $streamFactory,
                ),
            ],
        );
    }

    protected function toolDefinition(Tool $tool, McpContext $context): ToolDefinition
    {
        $readOnly = $tool->readOnly();
        $description = $tool->description();

        if (! $readOnly) {
            if ($context->actor->isGuest()) {
                $description = "Requires signing in. $description";
            } elseif (! $context->hasScope(McpContext::SCOPE_WRITE)) {
                $description = "Requires write access. $description";
            }
        }

        return new ToolDefinition(
            name: $tool->name(),
            title: $tool->title(),
            inputSchema: $tool->resolveInputSchema(),
            description: $description,
            annotations: new ToolAnnotations(
                title: $tool->title(),
                readOnlyHint: $readOnly,
                destructiveHint: $readOnly ? false : $tool->destructive(),
                idempotentHint: $tool->idempotent(),
                openWorldHint: $tool->openWorld(),
            ),
            outputSchema: $tool->outputSchema(),
        );
    }

    protected function resourceDefinition(Resource $resource): ResourceDefinition
    {
        return new ResourceDefinition(
            uri: $resource->uri(),
            name: $resource->name(),
            title: $resource->title(),
            description: $resource->description(),
            mimeType: $resource->mimeType(),
        );
    }

    protected function templateDefinition(ResourceTemplate $template): ResourceTemplateDefinition
    {
        return new ResourceTemplateDefinition(
            uriTemplate: $template->uriTemplate(),
            name: $template->name(),
            title: $template->title(),
            description: $template->description(),
            mimeType: $template->mimeType(),
        );
    }

    protected function promptDefinition(Prompt $prompt): PromptDefinition
    {
        $arguments = [];

        foreach ($prompt->arguments() as $name => $argument) {
            $arguments[] = new PromptArgument($name, $argument['description'] ?? null, $argument['required'] ?? false);
        }

        return new PromptDefinition(
            name: $prompt->name(),
            title: $prompt->title(),
            description: $prompt->description(),
            arguments: $arguments ?: null,
        );
    }

    /**
     * @return list<Icon>
     */
    protected function icons(): array
    {
        $icons = [];

        foreach ([$this->branding->faviconUrl(), $this->branding->logoUrl()] as $url) {
            if ($url && preg_match('#^https?://#', $url)) {
                $icons[] = new Icon($url, $this->mimeType($url));
            }
        }

        return $icons;
    }

    protected function mimeType(string $url): ?string
    {
        return match (strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'svg' => 'image/svg+xml',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
            default => null,
        };
    }
}
