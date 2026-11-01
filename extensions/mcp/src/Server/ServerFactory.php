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
use Flarum\Foundation\Config;
use Flarum\Foundation\ErrorHandling\Reporter;
use Flarum\Http\UrlGenerator;
use Flarum\Mcp\Capability\CapabilityRegistry;
use Flarum\Mcp\Capability\FailureReporter;
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
use Mcp\Schema\ServerCapabilities;
use Mcp\Schema\Tool as ToolDefinition;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server;
use Mcp\Server\Session\Psr16SessionStore;
use Mcp\Server\Session\SessionManager;
use Mcp\Server\Transport\Http\Middleware\CorsMiddleware;
use Mcp\Server\Transport\Http\Middleware\DnsRebindingProtectionMiddleware;
use Mcp\Server\Transport\StreamableHttpTransport;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

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

    public function make(McpContext $context, ResolvedCapabilities $capabilities, bool $withInstructions = true): Server
    {
        $invoker = new Invoker($this->errors, $this->container->tagged(Reporter::class));
        $failures = $this->container->make(FailureReporter::class);

        $builder = Server::builder()
            ->setServerInfo(
                name: 'flarum',
                // The full version is for admins only, as in the forum resource.
                version: $context->actor->isAdmin() ? Application::VERSION : explode('.', Application::VERSION)[0],
                description: $this->settings->get('forum_description') ?: null,
                icons: $this->icons() ?: null,
                websiteUrl: $this->url->to('forum')->base(),
                title: $this->settings->get('forum_title') ?: null,
            )
            ->setInstructions($withInstructions ? $this->overview->instructions($context, $capabilities) : null)
            ->setLogger($this->logger())
            // Only what the server does: no resource subscriptions or list-change
            // notifications (no long-lived streams), no logging, no completions.
            ->setCapabilities(new ServerCapabilities(
                tools: $capabilities->tools !== [],
                toolsListChanged: false,
                resources: $capabilities->resources !== [] || $capabilities->resourceTemplates !== [],
                resourcesSubscribe: false,
                resourcesListChanged: false,
                prompts: $capabilities->prompts !== [],
                promptsListChanged: false,
                logging: false,
                completions: false,
            ))
            // The PSR-16 store expires its own keys, so there is nothing to collect.
            // No session lock: the SDK's FileSessionLock leaves its lock files for
            // FileSessionStore::gc() to remove, and SymfonyLockSessionLock needs
            // symfony/lock; neither pairs with a PSR-16 store on Flarum's cache.
            ->setSession(sessionManager: new ActorSessionManager(
                new SessionManager(new Psr16SessionStore($this->cache, 'mcp-session-', static::SESSION_TTL), $this->logger(), gcProbability: 0),
                ActorSessionManager::ownerOf($context->actor, $context->request->getAttribute('ipAddress')),
            ));

        // The SDK validates each definition (a tool's name, an object input
        // schema, ...): one an extension got wrong is reported and left out.
        foreach ($capabilities->tools as $name => $tool) {
            $definition = $failures->attempt(fn () => $this->toolDefinition($tool, $capabilities->isReadOnly($name), $context));

            if ($definition) {
                $builder->add($definition, new ToolHandler($tool, $context, $invoker));
            }
        }

        foreach ($capabilities->resources as $resource) {
            $definition = $failures->attempt(fn () => $this->resourceDefinition($resource));

            if ($definition) {
                $builder->add($definition, new ResourceHandler($resource, $context, $invoker));
            }
        }

        foreach ($capabilities->resourceTemplates as $template) {
            $definition = $failures->attempt(fn () => $this->templateDefinition($template));

            if ($definition) {
                $builder->add($definition, new ResourceTemplateHandler($template, $context, $invoker));
            }
        }

        foreach ($capabilities->prompts as $prompt) {
            $definition = $failures->attempt(fn () => $this->promptDefinition($prompt));

            if ($definition) {
                $builder->add($definition, new PromptHandler($prompt, $context, $invoker));
            }
        }

        return $builder->build();
    }

    public function transport(ServerRequestInterface $request): StreamableHttpTransport
    {
        $responseFactory = new ResponseFactory();
        $streamFactory = new StreamFactory();

        $registry = $this->container->make(CapabilityRegistry::class);
        $host = parse_url($this->url->to('forum')->base(), PHP_URL_HOST) ?: 'localhost';
        $origins = $registry->allowedOrigins();

        return new StreamableHttpTransport(
            request: $request,
            responseFactory: $responseFactory,
            streamFactory: $streamFactory,
            logger: $this->logger(),
            middleware: [
                // Only same-origin pages, non-browser clients and the origins
                // extensions allow get through; only the forum's host and the
                // hosts extensions allow are answered.
                $this->cors(),
                new DnsRebindingProtectionMiddleware(
                    allowedHosts: array_values(array_unique([$host, ...$registry->allowedHosts()])),
                    allowedOrigins: $origins,
                    responseFactory: $responseFactory,
                    streamFactory: $streamFactory,
                ),
            ],
        );
    }

    /**
     * The CORS policy for /mcp. The controller applies it to authentication
     * challenges too, which never reach the transport.
     */
    public function cors(): CorsMiddleware
    {
        return new CorsMiddleware(allowedOrigins: $this->container->make(CapabilityRegistry::class)->allowedOrigins());
    }

    protected function logger(): LoggerInterface
    {
        return new SdkLogger(
            $this->container->make(LoggerInterface::class),
            $this->container->make(Config::class)->inDebugMode(),
        );
    }

    protected function toolDefinition(Tool $tool, bool $readOnly, McpContext $context): ToolDefinition
    {
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
