# flarum/mcp: Design Spec

**Status:** approved
**Flarum target:** ^2.1 (bundled, flarum/framework `2.x`)
**Date:** 2026-10-31

## Goal

A bundled extension that turns a forum into a compliant MCP server. A user connects
Claude, ChatGPT, VS Code, Cursor or any other MCP client to `https://forum.example/mcp`.
The client then acts as that user, or as a guest when it is not signed in, with exactly
that user's permissions. The server knows which extensions are enabled. It describes
them to the model and gives access to their API resources without the extension author
doing anything. Extensions that want a richer surface register tools, resources and
prompts through `Flarum\Mcp\Extend\Mcp`.

The work ships in two stacked branches:

| Branch | Worktree | Content |
|---|---|---|
| `im/oauth-provider-mcp` | `/workspace/oauth-provider-mcp` | `im/oauth-provider` (the user's WIP) merged with `origin/2.x`, plus the OAuth features MCP clients need (part A) |
| `im/mcp` | `/workspace/mcp` | stacked on the branch above: `extensions/mcp` plus core field descriptions (part B) |

## Non-Goals

- Long-running transports: SSE streams, GET streams, `subscriptions/listen`, resource
  subscriptions. Shared hosting / php-fpm has no long-lived process.
- Sampling, roots, elicitation and logging. They are deprecated in `2026-07-28` and the
  server does not need them.
- The `io.modelcontextprotocol/tasks` extension.
- An MCP *client* inside Flarum (that is flarum/ai's territory). flarum/mcp does not
  depend on flarum/ai.
- Integration with flarum/flags. It is being replaced by flarum/moderation.
- A stdio transport or a CLI command.

## Protocol

- SDK: `mcp/sdk` pinned to `~0.9.0`, the official PHP SDK (PHP Foundation and Symfony).
  It is experimental, so every SDK type stays inside `Flarum\Mcp\Server\*`. Extension
  authors only see Flarum's own `Tool` / `Resource` / `ResourceTemplate` / `Prompt`
  base classes. An SDK upgrade must never break a third-party extension.
- Both protocol eras through `StreamableHttpTransport`: the handshake era (`2025-03-26`
  to `2025-11-25`) and the stateless `2026-07-28` era (`server/discover`, `_meta`
  envelope, `Mcp-Method` / `Mcp-Name` headers). The SDK routes each request.
- The handshake-era session store is `Psr16SessionStore` on Flarum's cache
  (`Illuminate\Contracts\Cache\Repository`, which implements PSR-16), so it works under
  php-fpm on every cache driver.
- PSR-17 factories are passed explicitly (Laminas Diactoros, which core already ships),
  so the SDK's `php-http/discovery` lookup is never needed at runtime. The monorepo
  root `composer.json` sets `allow-plugins.php-http/discovery: false`.
- The server is built per request, for the request's actor (see Capability resolution).
  It costs one registry walk per request, with no persistent state.
- The transport middleware is `CorsMiddleware` and `DnsRebindingProtectionMiddleware`.
  `allowedHosts` is the forum's host. `allowedOrigins` is empty, so only same-origin
  browser requests and non-browser clients get through.
- Request body cap: the SDK default, 4 MiB.
- Server info:
  - name `flarum`
  - title: forum title
  - version: Flarum version
  - description: forum description
  - websiteUrl: forum URL
  - icons: forum favicon / logo when set

## Endpoints (forum routes)

| Method | Path | Name | Purpose |
|---|---|---|---|
| POST, GET, DELETE, OPTIONS | `/mcp` | `mcp` | MCP endpoint (GET answers 405 from the SDK) |
| GET | `/.well-known/oauth-protected-resource/mcp` | `mcp.resourceMetadata` | RFC 9728 Protected Resource Metadata |
| GET | `/.well-known/oauth-protected-resource` | `mcp.resourceMetadata.root` | same document, root fallback |

- The canonical resource URI is `{forum base URL}/mcp`. The resource metadata lists
  the forum issuer as its only authorization server, with `scopes_supported:
  ["mcp:read", "mcp:write"]` and `resource_name` set to the forum title.
- `/mcp` is CSRF-exempt and **never authenticates from the session cookie**. The
  controller discards any session actor and derives the actor only from the
  `Authorization` header. That rules out CSRF, so the exemption is safe.
- Known limitation: forums installed under a sub-path (`example.com/forum`) cannot serve
  the host-root `/.well-known/*` paths. The admin page states the exact rewrite rule
  needed for that case.

## Authentication and the actor

All of this lives in one middleware, `Flarum\Mcp\Http\AuthenticateMcpRequest`, run
before the transport:

1. **`Authorization: Bearer <jwt>`**: validated by flarum/oauth-provider's
   `TokenValidator` with resource `{base}/mcp`. That checks the signature, expiry,
   revocation and `aud`. The actor is the token's user and the scopes are the token's
   scopes.
2. **`Authorization: Bearer <developer token>`**: a core `AccessToken` whose `type` is
   `developer` (`DeveloperAccessToken`), checked with `AccessToken::findValid()` plus a
   type check. Session and remember tokens are refused. Scopes are `mcp:read` and
   `mcp:write`. This serves VS Code, Cursor and scripts. The token is created on the
   user's Security page as usual.
3. **`Authorization: Token <key>`** (core API key / access token header): not accepted.
   It returns 401 `invalid_token`, because master API keys must never become an MCP
   identity.
4. **No Authorization header**: the actor is the guest and the scope is `mcp:read`.
5. **A malformed or invalid token**: 401 with
   `WWW-Authenticate: Bearer error="invalid_token", resource_metadata="{base}/.well-known/oauth-protected-resource/mcp", scope="mcp:read mcp:write"`.

Then the permission gate:

- `mcp.use` is a new permission, shown in the admin permission grid under a new "MCP"
  heading. It is granted to Members by default; the migration seeds `group_permission`
  for `Group::MEMBER_ID`. Granting it to Guests is how an admin enables anonymous access.
- If the actor lacks `mcp.use`:
  - A guest gets 401 with the challenge above, so the client starts sign-in ("lazy
    authentication").
  - A signed-in user gets 403 with JSON-RPC-free body
    `{"error":"forbidden","error_description":"..."}`.
- A suspended user is a normal actor whose permissions are already reduced by
  flarum/suspend. Nothing special is needed.
- `actor->updateLastSeen()` is throttled the same way as `AuthenticateWithHeader`.

Scope enforcement:

- `tools/call` of a tool with `readOnly() === false` needs `mcp:write`.
  - For a guest, it answers HTTP 401 with challenge `scope="mcp:read mcp:write"`.
    Write tools always need a registered user.
  - For a token without `mcp:write`, it answers HTTP 403
    `WWW-Authenticate: Bearer error="insufficient_scope", scope="mcp:read mcp:write", resource_metadata=...`.
- `ApiClient` enforces it again at the data layer, whatever a tool declares: every
  non-GET call (and every command it dispatches) needs `mcp:write` and a registered
  actor, or it raises a `ToolError`. The middleware check is the user-facing answer.
- This check happens in the middleware: it peeks at the JSON-RPC body (`method` /
  `params.name`; a batch is checked per message) before handing the unchanged body to
  the transport. The SDK's `ScopePolicy` is not used, because it requires a token for
  every request.
- `tools/list` still lists write tools to guests and read-only tokens, so the model can
  discover them and step up. Their descriptions are prefixed "Requires signing in" or
  "Requires write access" accordingly.

## Capability model (public API)

Namespace `Flarum\Mcp\Capability`. These are abstract classes, resolved from the
container, so constructor injection works.

```php
abstract class Tool
{
    abstract public function name(): string;            // snake_case, ≤ 64 chars, [a-z0-9_]
    abstract public function title(): string;
    abstract public function description(): string;
    abstract public function inputSchema(): array;       // JSON Schema object
    public function outputSchema(): ?array { return null; }
    public function readOnly(): bool { return false; }   // read tools declare true
    public function destructive(): bool { return false; } // ignored when readOnly
    public function idempotent(): bool { return $this->readOnly(); }
    public function openWorld(): bool { return false; }
    /** Whether the actor sees the tool at all (permission-based, cheap). */
    public function isAvailableFor(User $actor): bool { return true; }
    abstract public function handle(array $arguments, McpContext $context): mixed;
}

abstract class Resource          // fixed URI
{
    abstract public function uri(): string;
    abstract public function name(): string;
    public function title(): ?string;
    public function description(): ?string;
    public function mimeType(): string { return 'application/json'; }
    public function isAvailableFor(User $actor): bool { return true; }
    abstract public function read(McpContext $context): mixed;
}

abstract class ResourceTemplate  // RFC 6570 template
{
    abstract public function uriTemplate(): string;     // e.g. flarum://discussions/{id}
    abstract public function name(): string;
    public function title(): ?string; public function description(): ?string;
    public function mimeType(): string { return 'application/json'; }
    public function isAvailableFor(User $actor): bool { return true; }
    abstract public function read(array $variables, McpContext $context): mixed;
}

abstract class Prompt
{
    abstract public function name(): string;
    public function title(): ?string; public function description(): ?string;
    /** @return array<string, array{description?: string, required?: bool}> */
    public function arguments(): array { return []; }
    public function isAvailableFor(User $actor): bool { return true; }
    /** @return list<array{role: 'user'|'assistant', text: string}>|string */
    abstract public function get(array $arguments, McpContext $context): array|string;
}
```

`McpContext` carries:
- `actor`: the `User`.
- `scopes`: `list<string>`.
- `api`: an `ApiClient`, see below.
- `url`: `UrlGenerator`.
- `translator`.
- `settings`.
- `forumUrl(string $path)`: a helper.
- `request`: the original PSR-7 request.

Errors:
- `Flarum\Mcp\Exception\ToolError` (`new ToolError('message')`) becomes a tool result
  with `isError: true`, so the model can correct itself.
- For resources it becomes JSON-RPC `-32602`.
- Any other exception is logged through Flarum's error reporter and surfaces as a
  generic internal error. It never leaks a stack trace, even in debug mode.

Return values:
- An array becomes `structuredContent` plus a JSON text block. The structured content is
  only sent when the tool has an object `outputSchema`; otherwise the result is JSON
  text only.
- A string becomes text content.
- An `Mcp\Schema\Result\CallToolResult` is never required from extension code; the
  adapter wraps the value.

The adapter (`Flarum\Mcp\Server\ServerFactory`) maps these classes onto the SDK's
`Builder::add(definition, handler)`. A handler wraps the call and translates
`ToolError`, `ValidationException`, `PermissionDeniedException`,
`ModelNotFoundException`, JSON:API exceptions and `FloodingException` into
`ToolCallException` messages:
- validation and JSON:API errors read "`<field>`: message"
- permission errors read "You do not have permission to …"
- missing models read "Not found" (no existence leak)
- flooding reads "You are doing that too often; wait before trying again".

## `ApiClient`: calling JSON:API as the actor

`Flarum\Mcp\Api\ApiClient` is the only way the extension's own tools touch data. Third
parties are encouraged to use it too.

```php
$context->api->index(string $type, array $query = []): ApiResult;   // filter, sort, page, include, fields
$context->api->show(string $type, string $id, array $query = []): ApiResult;
$context->api->create(string $type, array $attributes, array $relationships = [], array $query = []): ApiResult;
$context->api->update(string $type, string $id, array $attributes, array $relationships = [], array $query = []): ApiResult;
$context->api->delete(string $type, string $id): void;
$context->api->endpoint(string $type, string $endpoint, string $method, string $path, array $query = [], ?array $body = null): ApiResult; // custom endpoints
```

- Every call builds a real `ServerRequest` and runs
  `JsonApi::forResource(..)->forEndpoint(..)->handle($request)`. That covers method,
  URI `/api/...`, query params, parsed JSON:API body, the actor
  (`RequestUtil::withActor`), the `routeName` attribute `"{type}.{endpoint}"` (with the
  resource's `routeNamePrefix()` when it has one) and `bypassCsrfToken`. `process()` is
  **never** used: it skips the endpoint's `authenticated()/can()` gates and the model
  lookup (findings FND-258, FND-291).
- Before `handle()`, the client calls the `flarum.api.throttlers` stack (the
  `ThrottleApi` middleware's `throttle($request)`) with the same request. Core's post
  flood control and every extension throttler therefore apply to MCP writes.
  `bypassThrottling` is never set.
- The client resolves the resource by type through `JsonApi::getResource($type)`.
  Endpoints are looked up by name on `resolveEndpoints()`.
- `ApiResult` wraps the decoded JSON:API document:
  - `data()` returns the raw document.
  - `simplify()` returns the compact shape below.
  - `meta()`, `links()`.

The compact shape (used in every tool output):

```json
{ "type": "discussions", "id": "12", "title": "...", "commentCount": 4,
  "relationships": { "user": {"type":"users","id":"3"}, "tags": [{"type":"tags","id":"1"}] } }
```

The `included` resources are simplified the same way and returned under `included`,
de-duplicated. Normalisation:
- Attributes ending in `Html` are converted to plain text with paragraph breaks and
  links kept as `text (url)`. They are emitted under the same name without the `Html`
  suffix, unless a raw attribute of that name already exists.
- String values over 4000 characters are truncated with `… [truncated, N more characters]`.
- The whole tool payload is capped at 100 000 characters. On overflow, list results
  are cut and `meta.truncated: true` plus a `next` page hint is added.

## Generic tools (auto-discovered)

These cover every 2.x API resource, including third-party ones, with no work from
their authors. Registry: `JsonApi`'s resource list, read when the server is built.

| Tool | Read-only | Purpose |
|---|---|---|
| `describe_api` | yes | Without arguments: every exposed resource type, with its owning extension, description, available operations, and the attribute / relationship / filter / sort names. With `type`: the full description of that type: each attribute (JSON Schema from `getSchema()`, `description`, writable on create / update, nullable), relationships (target type(s), to-one / to-many, includable), filters (keys from `flarum.search.filters` for the resource's searcher, plus `q` when the driver supports full text), sorts (`sorts()` names, with `-` for descending), the default sort and the page limit. |
| `list_resources` | yes | Index endpoint: `type`, `filter` (object of strings), `sort`, `limit` (≤ 50), `offset`, `include` (list) |
| `get_resource` | yes | Show endpoint: `type`, `id`, `include` |
| `create_resource` | no | Create endpoint: `type`, `attributes`, `relationships` (`{name: id \| [ids]}`; the type is inferred from the relationship's single target type, else `{type,id}` is required) |
| `update_resource` | no | Update endpoint: `type`, `id`, `attributes`, `relationships` |
| `delete_resource` | no, destructive | Delete endpoint: `type`, `id` |

Exposure rules:

- A resource type is *exposed* when it is an `AbstractResource` and not hidden. Hidden
  by default (core): `access-tokens`, `mail-settings`, `extension-readmes`,
  `system-info`, plus anything the extender hides.
- Read operations (`index`, `show`) are exposed for every exposed type that has the
  endpoint.
- Write operations (`create`, `update`, `delete`) are exposed only when allow-listed:
  - Core allows `discussions`, `posts`, `users` (update only), `groups` and
    `notifications` (update only).
  - Bundled integrations allow their own types; see below.
  - Third parties opt in through `Extend\Mcp::allowWrites()`.
- Calling an operation that is not exposed gives `ToolError("... is not available
  through MCP")`.
- `describe_api` and the `type` enum in the generic tools' `inputSchema` list only the
  types and operations exposed to this actor. A resource or endpoint whose `Endpoint`
  is `admin()`-gated is omitted for non-admins: the endpoint's `isVisible`
  authorization is checked with a context built for the actor. Endpoint-level
  `can()`/`authenticated()` still run on every call.
- Custom (non-CRUD) endpoints are not exposed generically. An extension exposes one with
  `Extend\Mcp::endpointTool()`.

## Curated tools, resources and prompts

### Core (always registered)

| Name | Kind | Read-only | Notes |
|---|---|---|---|
| `search_discussions` | tool | yes | Arguments: `query?`, `author?` (username), `sort` (`latest` \| `top` \| `newest` \| `oldest`), `limit` ≤ 50, `offset`, plus integration arguments (for example `tag`). Returns id, title, url, author, createdAt, lastPostedAt, commentCount, and the first post excerpt (300 chars, only when it was included in the search result). |
| `read_discussion` | tool | yes | `id`, `near` (post number), `limit` ≤ 50. Returns discussion metadata plus comment posts (number, id, author, createdAt, editedAt, plain-text content, url) and paging hints. Event posts are rendered as one-line summaries. |
| `get_user` | tool | yes | `username` or `id`. Profile fields visible to the actor. |
| `whoami` | tool | yes | The actor's username, display name, groups, and a list of what they can do (see the overview). |
| `list_notifications` | tool | yes | Requires sign-in. Unread first, limit ≤ 50. |
| `start_discussion` | tool | no | `title`, `content` (Markdown / BBCode as the forum supports), plus integration args (`tags`). |
| `reply_to_discussion` | tool | no | `discussionId`, `content`. |
| `edit_post` | tool | no | `postId`, `content`. |
| `mark_notifications_read` | tool | no | `ids?` (all when omitted). |
| `flarum://forum` | resource | n/a | The forum overview (below). |
| `flarum://discussions/{id}` | template | n/a | Same output as `read_discussion` (first 20 posts). |
| `flarum://users/{username}` | template | n/a | Same output as `get_user`. |
| `summarize_discussion` | prompt | n/a | Argument `id`. Asks the model to read the discussion via `read_discussion` and summarise it. |
| `draft_reply` | prompt | n/a | Arguments `id` and `intent`. Asks the model to read the discussion and draft a reply for the user to review before posting. |

### Bundled integrations

Each lives in `extensions/mcp/src/Integration/<Ext>/` and is registered in mcp's own
`extend.php` through `(new Extend\Conditional())->whenExtensionEnabled('flarum-<ext>', [...])`.
That way the bundled set exercises the same public extender third parties use.

| Extension | Adds |
|---|---|
| tags | `list_tags` tool (tag tree visible to the actor, with slug, description, counts, and whether the actor can start discussions in it). A `tag` argument (slug) on `search_discussions`. A `tags` argument (list of slugs) on `start_discussion`. Writes allowed: none (tag admin stays in the admin UI). |
| likes | `like_post` and `unlike_post` tools (update `posts` with `isLiked`). |
| subscriptions | `follow_discussion` tool, with `state` `follow` \| `ignore` \| `none`. |
| lock, sticky | Generic `update_resource` on `discussions` with `isLocked` / `isSticky` (already allowed). They contribute `instructions()` only. |
| messages | Tools `list_conversations`, `read_conversation`, `send_message` (requires sign-in; read and write follow flarum/messages' permissions). |
| mentions | `instructions()` describing the mention syntax (`@"Display Name"#p123`, `@username`). |
| suspend, approval | Generic writes allowed on the attributes they add (`suspendedUntil`, `suspendReason`, `isApproved`); the field gates do the authorization. |

To add an argument to another tool's schema, a tool exposes a hook:
`Tool::extendSchema(callable)` and `Tool::extendHandler(callable)` through
`Extend\Mcp::extendTool($name, schema: fn (array $schema) => ..., handler: fn (array $args, array $apiQuery, McpContext $ctx) => $apiQuery)`.
The handler hook maps an extra argument onto the underlying API query or body. Core
tools whose arguments can be extended this way are `search_discussions` (`$apiQuery`
is the index query) and `start_discussion` (`$apiQuery` is
`['attributes' => .., 'relationships' => ..]`).

## Extension awareness: the overview and instructions

`flarum://forum` (JSON), also used for the server `instructions` text, contains:

- `forum`: title, description, url, locale, version.
- `you`: guest flag, username, display name, groups, and a capability list derived from
  `actor->can()` on the global abilities: `startDiscussion`, `viewForum`,
  `searchUsers`, `viewUserList`, `discussion.reply` (global check), plus any abilities
  extensions add through `Extend\Mcp::ability($ability, $label)`.
- `extensions`: every **enabled** extension with:
  - id, title, description, version (from `ExtensionManager`)
  - the MCP tools / resources / prompts it contributes (from extender registration
    provenance)
  - the API resource types it owns
  - its `instructions` text

  Type ownership is resolved by matching the resource class against the extension's
  PSR-4 namespaces from its composer.json `autoload`. Core types are owned by `core`.
- `guidance`: short text on how to use the server (prefer the curated tools, use
  `describe_api` for anything else, write tools change the real forum, …).

The server `instructions` string is generated from the same data as a short Markdown
text (≤ 2000 chars): forum name and purpose, who you are, enabled extensions with
one-line descriptions, then the guidance.

## `Flarum\Mcp\Extend\Mcp` (third-party extender)

```php
(new Flarum\Mcp\Extend\Mcp())
    ->tool(MyTool::class)                       // Tool subclass
    ->resource(MyResource::class)
    ->resourceTemplate(MyTemplate::class)
    ->prompt(MyPrompt::class)
    ->removeTool('search_discussions')          // also removeResource / removePrompt
    ->extendTool('search_discussions', schema: fn (array $s) => $s, handler: fn (array $args, array $q, McpContext $c) => $q)
    ->describe('polls', 'Polls attached to discussions. Each poll has options and votes.')
    ->allowWrites('polls', ['create', 'update', 'delete'])
    ->hide('poll-votes')                        // hide a type from the generic tools
    ->hideEndpoint('polls', 'delete')
    ->endpointTool('polls', 'close', name: 'close_poll', title: 'Close a poll', description: '...')   // read-only iff the endpoint is a GET
    ->ability('polls.start', 'Start polls')
    ->instructions('Use close_poll to end a poll early.');   // string or callable(McpContext): ?string
```

- Extensions use it wrapped in `Extend\Conditional::whenExtensionEnabled('flarum-mcp', ...)`
  so that flarum/mcp stays an optional dependency for them.
- The extender records the calling `Extension` (the second argument of `extend()`) for
  overview provenance.
- Field descriptions use the existing json-api-server API: `->description('...')` on a
  field definition, or on someone else's field through
  `(new Extend\ApiResource(X::class))->field('name', fn ($f) => $f->description('...'))`.
- Removing a tool twice, or extending a missing tool, is a no-op. A misconfiguration
  must never break `/mcp`.

## Core changes (part B, same branch)

- `->description()` on the fields of core resources: `discussions`, `posts`, `users`,
  `groups`, `forum`, `notifications`. The bundled extensions get descriptions on the
  fields they add: tags, likes, lock, sticky, subscriptions, approval, suspend,
  messages, mentions, nicknames.
  - Descriptions are one short English sentence (developer-facing, like OpenAPI
    docs; not translated).
  - A field is documented only where the name is not self-explanatory, or where its
    format matters (dates, `content` source syntax, enums).
- A resource-level description: json-api-server has no resource description, so
  flarum/mcp keeps descriptions for core types itself (`describe()` calls in mcp's own
  extend.php). This avoids a core API change.

## Admin UI

- An extension settings page (Mithril, following flarum/core admin patterns) showing:
  - the MCP server URL with a copy button, and the resource-metadata URL
  - connection steps for Claude (custom connector URL), ChatGPT (connector URL), VS Code
    (`.vscode/mcp.json` snippet with the URL), and Cursor / others with a developer
    token (`Authorization: Bearer`, link to the user's Security page)
  - a read-only table of registered tools / resources / prompts with their source
    extension and read / write marker, loaded from
    `GET /api/mcp-capabilities` (admin-only resource, no Show/Create)
  - a warning when the forum lives under a sub-path, with the `.well-known` rewrite rule
- Permission grid: `mcp.use` under a new "MCP" section (`Extend\Permission`-style
  admin JS `app.extensionData.for('flarum-mcp').registerPermission(...)`).
- No settings keys in v1.

## Locale

`extensions/mcp/locale/en.yml`:
- `flarum-mcp.admin.*`: page, permission label, connection guide.
- `flarum-mcp.api.*`: errors surfaced to the model (`not_exposed`, `requires_sign_in`,
  `requires_write_scope`, `not_found`, `throttled`, `forbidden`).

Tool and field descriptions stay English and are not translated. They are
model-facing, and a single language keeps tool definitions stable.

## Part A: flarum/oauth-provider additions (branch `im/oauth-provider-mcp`)

The extension is unreleased, so its **migrations are edited in place**; there is no
upgrade path to maintain.

- **A1. Client ID Metadata Documents (draft-ietf-oauth-client-id-metadata-document).**
  - When `client_id` is an `https://` URL with a path, the authorize endpoint fetches it:
    Guzzle, 5 s timeout, at most 5 redirects, max body 64 KiB, `application/json`.
  - The fetch is refused when the host resolves to a loopback, private, link-local or
    reserved IP (SSRF guard). Debug mode allows loopback for local testing.
  - The document must have:
    - `client_id` equal to the URL
    - `client_name` and `redirect_uris`
    - `token_endpoint_auth_method` absent or `none`. Only public clients come from
      metadata documents; a `client_secret` in the document gets it refused.
  - Optional fields: `logo_uri`, `client_uri`.
  - The result is upserted into `oauth_provider_clients` with
    `source = 'metadata_document'` and `metadata_expires_at` taken from `Cache-Control:
    max-age`, clamped between 5 min and 24 h (default 1 h). It is refetched when
    expired.
  - Admins can revoke such a client like any other.
  - On the consent screen, a metadata-document client shows its name plus "from
    `<host of client_id>`", marked unverified.
- **A2. Dynamic Client Registration (RFC 7591)** at `POST /oauth/register`, CSRF-exempt,
  rate-limited to 10 per IP per hour through the cache.
  - Public clients only (`token_endpoint_auth_method: none`).
  - Redirect URIs must be `https://` or loopback `http://`.
  - The response follows RFC 7591, with no registration access token.
  - Setting `flarum-oauth-provider.allow_dynamic_registration`, default `true`, shown on
    the admin page. When it is off the endpoint returns 404 and is omitted from metadata.
  - These clients are stored with `source = 'dynamic'`.
- **A3. Resource indicators (RFC 8707).**
  - `resource` parameter on authorize and token requests. It is stored on the auth code
    and the access token (`resource` column, nullable, 2048) and carried over on refresh.
  - The access token JWT's `aud` is the resource when one is given, else the client id
    (the current behaviour).
  - Resources must be registered through a new `Flarum\OAuthProvider\Extend\Resource`
    extender: `new Resource(fn (UrlGenerator $url) => $url->to('forum')->route('mcp'), scopes: ['mcp:read', 'mcp:write'])`.
    It takes a callable because the base URL is only known at runtime. An unknown
    resource gets `invalid_target`.
  - Scopes requested with a resource must belong to that resource or be OIDC scopes.
- **A4. Authorization server metadata (RFC 8414)** at
  `/.well-known/oauth-authorization-server`, served alongside the existing OIDC document.
  Both documents gain:
  - `code_challenge_methods_supported: ["S256"]` (`plain` is dropped)
  - `client_id_metadata_document_supported: true`
  - `registration_endpoint` when DCR is on
  - `authorization_response_iss_parameter_supported: true`, with `iss` added to the
    authorization redirect (RFC 9207)
  - `revocation_endpoint`

  Token revocation (RFC 7009) is added at `POST /oauth/revoke`.
- **A5. PKCE.** S256 is required for every public client. Confidential clients may still
  omit it.
- **A6. Loopback redirect URIs (RFC 8252 §7.3).**
  - For public clients, a registered redirect URI on `127.0.0.1`, `[::1]` or `localhost`
    matches a request URI that differs only in port.
  - Scheme, host and path must still match exactly.
- **A7. `Flarum\OAuthProvider\ResourceServer\TokenValidator`**, a container singleton:
  ```php
  public function validate(#[\SensitiveParameter] string $jwt, string $resource): ?ValidatedToken;
  // ValidatedToken: readonly User $user, string $clientId, list<string> $scopes, string $tokenId, ?string $resource
  ```
  - It verifies the signature with the provider key, then `exp`/`nbf`, the token row
    (not revoked, not expired), `aud` containing `$resource`, and that the user exists.
  - It returns null on any failure and never throws for bad input.
  - On success it touches a new `last_used_at` column on the token row, throttled to
    once per 60 s.
- **A8. Connected applications (forum settings page).**
  - A "Connected applications" section on the user's Settings page, listing their
    consents: client name, logo, source host, scopes, granted at, last used.
  - "Revoke" deletes the consent and revokes all of that user's access and refresh
    tokens for the client.
  - The data comes from a new API resource `oauth-consents`: index scoped to the actor,
    delete only own. Admins have no special access.
- **A9. Column changes (in place).** In the clients table:
  - `id` becomes `string(255)`, with every foreign key column matched
  - added: `source` (`admin` | `metadata_document` | `dynamic`), `logo_uri`,
    `client_uri`, `metadata_expires_at`

  Plus `resource` on auth codes and access tokens, and `last_used_at` on access tokens.
- `composer.json` constraint becomes `flarum/core: ^2.1`.

Scopes for MCP (`mcp:read`, `mcp:write`) are registered by flarum/mcp through
oauth-provider's `Extend\Scope`, together with the resource through `Extend\Resource`.

## Cross-extension integration

- flarum/mcp `require`s `flarum/core ^2.1`, `flarum/oauth-provider *@dev` (monorepo
  version), and `mcp/sdk ~0.9.0`.
- Bundled integrations are soft (`Extend\Conditional`) on tags, likes, subscriptions,
  lock, sticky, messages, mentions, suspend and approval.
- No dependency on flarum/ai or flarum/flags.
- `flarum-monorepo.json` gains `oauth-provider` (already on the branch) and `mcp`.

## Testing surface

Integration tests (SQLite, `composer test`) in `extensions/mcp/tests/integration`:

- **Transport and protocol.**
  - Handshake era: `initialize`, then `tools/list` with `Mcp-Session-Id` (the session
    store persists over the cache between two requests).
  - Modern era: `server/discover` and `tools/list` with the `_meta` envelope and
    `Mcp-Method` headers.
  - GET returns 405.
  - A foreign `Origin` returns 403.
- **Auth matrix.** For each case, assert both the HTTP status and the `WWW-Authenticate`
  value. Each line is one test case:
  - no token, Guests without `mcp.use`: 401 with resource_metadata
  - no token, Guests with `mcp.use`: tools listed, and a read-only call works
  - guest calls a write tool: 401 with scope `mcp:read mcp:write`
  - developer token: user actor, write works
  - session-type token: 401
  - `Token` header API key: 401
  - OAuth JWT with the right `aud`: user actor
  - OAuth JWT with another `aud`: 401
  - revoked JWT: 401
  - read-only JWT calling a write tool: 403 insufficient_scope
  - a user without `mcp.use`: 403
  - a cookie session with no Authorization header is treated as a guest
- **Visibility.**
  - A private discussion (tags restricted) is invisible in `list_resources`,
    `search_discussions`, `get_resource` (404 text) and `read_discussion`, and visible to
    a privileged actor.
  - The `describe_api` type list differs between an admin and a member (`admin()`
    endpoints).
- **Writes.**
  - `start_discussion` and `reply_to_discussion` create content as the actor, with
    validation errors returned as `isError` tool results.
  - Flood control: two replies within the throttle window; the second is a throttled
    `isError`.
  - A write on a type that is not allow-listed is refused.
- **Extender.** A test extension (through `$this->extend()`) registers a tool, removes a
  core tool, extends `search_discussions`, `describe()`s a type, and `allowWrites()`. All
  of it is observable through `tools/list` and `describe_api`.
- **Overview.** It lists enabled extensions (tags enabled in the test), the tools they
  contribute, and resource type ownership.
- **Integrations.** One happy-path test per bundled integration tool (tags, likes,
  subscriptions, messages).

oauth-provider tests (`extensions/oauth-provider/tests`):
- CIMD: happy path with a mocked HTTP client bound in the container; mismatched
  `client_id`; private-IP refusal; cache expiry refetch.
- DCR: on and off; non-https redirect refused.
- `resource` gives `aud`; unknown resource gives `invalid_target`; carried over on refresh.
- RFC 8414 document fields; `iss` on the redirect; PKCE plain refused; loopback port match.
- Revocation endpoint; `TokenValidator` (valid, wrong aud, expired, revoked).
- `oauth-consents` resource scoping (a user cannot see or delete others' consents);
  revoke cascades to tokens.

## Open Questions

None blocking. Calls made without asking (each is easy to reverse):

- Generic write access for third-party types is opt-in (`allowWrites`); reads are
  automatic.
- `mcp.use` defaults to Members; anonymous access means granting it to Guests.
- Tool / field descriptions stay English.
- DCR is on by default, even though the spec deprecates it, because VS Code and older
  Claude clients rely on it.
- Developer tokens are accepted as `Bearer` on `/mcp` only. Core's
  `AuthenticateWithHeader` is unchanged.

## References

- MCP spec `2026-07-28` and `2025-11-25`; the mcp/sdk v0.9.0 docs (`docs/run/*.md`).
- Findings:
  - FND-258, FND-291: use `JsonApi::handle()`, not `process()`
  - FND-163: token requests bypass CSRF
  - FND-028: CSRF exemptions in tests
  - FND-123: resources are singletons, so no per-instance caches
  - FND-098: the three error families to catch
  - FND-264: lowercase query params; use `filter[...]`
  - FND-312: a new bundled extension needs a built `js/dist` for `/admin` tests
  - FND-153: take phpunit config from the CLI skeleton
  - FND-310: write-only secret settings
