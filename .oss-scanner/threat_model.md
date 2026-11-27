# Threat model

The scanner reads this file before it starts. It covers Flarum 2.x: core and the bundled extensions in the
flarum/framework monorepo. Severity follows the CVSS 4.0 vectors Flarum publishes on its GitHub security advisories.

## What this project does and where untrusted input enters

Flarum is PHP forum software. A forum is a public web application, so treat all HTTP input as untrusted:

- the JSON:API under `/api`: request bodies, and the `include`, `filter`, `sort`, `page` and `fields` query
  parameters on every resource;
- post and message content, which users write in Markdown or BBCode and the s9e TextFormatter parser and renderer turn
  into HTML (`flarum/markdown`, `flarum/bbcode`, `flarum/mentions`, `flarum/emoji`), plus edits, previews and
  quoting;
- other stored values that users set and other users later see: usernames, display names (`flarum/nicknames`), bios,
  discussion titles, tag names, group names, flag reasons, suspension messages, notification and email bodies;
- avatar uploads, and the frontend's own HTML and JavaScript that renders all of the above (`js/src` in each package);
- the session and remember-me cookies, the CSRF token, access tokens and API keys sent in the `Authorization`
  header, login, registration, email confirmation, password reset and email change;
- the OAuth 2 / OpenID Connect provider (`flarum/oauth-provider`: `/oauth/*`, dynamic client registration, the
  `.well-known` endpoints) and the MCP server (`flarum/mcp`: `/mcp`), which act on behalf of the authorising user;
- the realtime websocket (`flarum/realtime`), which pushes forum events to connected clients.

### Where the code is

| Path | What it is |
| --- | --- |
| `/src` | The flarum/framework checkout (the `2.x` branch). Core is `framework/core`, bundled extensions are `extensions/<name>`, shared PHP packages are `php-packages/*`. Patches belong here. |
| `/opt/flarum` | A working forum built from the flarum/flarum skeleton, served by Apache with the skeleton's `.htaccess`, on MariaDB, debug mode off. Every monorepo package is installed from `/src` as a symlink, so PHP edits in `/src` are live on it, except what Flarum caches (see "How to exercise it"). |

### Trust model

- **Guests** are untrusted.
- **Users whose email is unconfirmed, and suspended users** (other than admins), get only the guest group's
  permissions. They are
  still signed in, so they keep what their own account and existing participation give them: confirming the
  email, reading and editing their own data, and reading private conversations they are in (and replying where
  the conversation allows it). Anything beyond that is a finding.
- **Members** must stay within the permissions their groups grant. Permissions are configured per group and,
  through `flarum/tags`, per tag (`tag<id>.<permission>`). Restricted tags, unapproved content, hidden or deleted
  posts, private messages (`flarum/messages`) and other users' private attributes (email address, IP addresses,
  notification and preference settings) are confidential to those allowed to see them.
- **Moderators** are members with extra permissions an admin grants (in the seeded forum, the Mods group). They are
  trusted only as far as those permissions go: a moderator gaining admin rights, or a permission they were not
  given, is a finding.
- **Admins** (the Admin group, ID 1) are trusted with the whole forum. By design they can add raw HTML and
  JavaScript to every page (custom header and footer), write custom LESS, change any setting, manage every
  user and group, and, with `flarum/extension-manager` enabled, install Composer
  packages, which is code execution. Something an admin can already do through these features is **not** a
  finding. An admin-only issue is reported only if it reaches beyond the forum to the server (reading or writing
  files outside the forum's storage, code execution with the extension manager disabled, SSRF into the server's
  network) and is always rated **Low** (see below).
- `config.php`, the environment, the web server, the file system and the database are trusted operator input.

## Components that matter most / least

Highest priority:

1. Access bypass: policies (`Access/*Policy.php`), visibility scopes (`whereVisibleTo`, `Access/Scope*`), resource
   field and relationship visibility and writability in `Api/Resource/*`, `include` paths that expose related
   models the actor cannot see, filters, searches and sorts that leak hidden data through counts, ordering or
   timing, and the tag-scoped permission checks in `flarum/tags`.
2. Privilege escalation: group membership changes (`editGroups`), permission grants, user attribute writes
   (email, password, `isEmailConfirmed`), suspension and approval bypass.
3. Account takeover: password reset, email confirmation and email change tokens, session and remember-me
   handling, access tokens and API keys, OAuth authorisation codes, refresh tokens, redirect URIs and PKCE, MCP
   authorisation.
4. XSS: TextFormatter configuration and rendering (including `flarum/mentions`, which renders other users' and
   posts' data), values rendered without escaping in Mithril (`m.trust`, `innerHTML`), email templates, and
   anything the forum embeds into pages server-side (`Frontend` documents, preloaded JSON).
5. CSRF on state-changing routes, and open redirects (`return` and redirect parameters, logout).
6. SQL injection through Eloquent / the query builder (raw expressions, user-controlled column names in sorts and
   filters), path traversal and unsafe file handling in uploads and the asset compiler, unsafe deserialisation,
   and SSRF where the server fetches a user-supplied URL.

Lower priority or out of scope:

- `vendor/` and `node_modules/`: report these upstream, unless Flarum's own code uses a library unsafely.
- The compiled bundles in `js/dist/` and `js/dist-typings/`, which a bot generates from `js/src/`. Report and patch
  the source.
- Test code (`tests/`), `php-packages/testing`, `php-packages/phpstan`, and the `Testing` namespaces.
- `flarum/pusher` (not enabled: `flarum/realtime` replaces it), `flarum/akismet` and `flarum/ai`'s calls to
  external services, which cannot be reached offline. Their local code is in scope.
- Flarum 1.x and third-party extensions.

## How to exercise it

- Run `flarum-start` first. It starts MariaDB, PostgreSQL, Apache and flarum/realtime's websocket daemon (the
  container has no init system) and is safe to run again. The forum is then at `http://127.0.0.1/` and the websocket at
  `127.0.0.1:6001`. `flarum-stop` stops them all.
- Seeded accounts, all with the password `password`:
  - `admin` (user 1): the Admin group, fully trusted
  - `mod` (user 3): the Mods group (ID 4)
  - `member` (user 2) and `other` (user 4): plain members
  - guests: no login
- Seeded content: the public `General` tag (ID 1) with a discussion by `member` and a reply by `other`; a
  restricted primary `Staff` tag (ID 2) that Mods (and admins) can view, start discussions in and reply in, with one
  discussion by `mod`; and a private conversation between `member` and `admin` that `other` must not see.
- `flarum-token <username>` prints an access token for a seeded user; send it as `Authorization: Token <token>`.
  A request with a token skips the CSRF check. To test CSRF, use a session instead: `GET /` returns a session cookie
  and an `X-CSRF-Token` response header; send both with `POST /login` (`identification`, `password`), then use the
  new session cookie and token. Reproducers may use either form.
- `flarum <command>` runs the forum's console (`/opt/flarum/flarum`) as `www-data`, the Apache user:
  `flarum cache:clear`, `flarum info`, `flarum extension:enable <id>`. The database is
  `mysql://flarum:flarum@127.0.0.1/flarum`. Mail goes to the log driver; outgoing emails, including password-reset
  and confirmation links, are written to `/opt/flarum/storage/logs/`.
- Errors are logged, not shown in responses: Flarum's own to `/opt/flarum/storage/logs/`, PHP's to
  `/var/log/php/error.log`, Apache's to `/var/log/apache2/flarum-error.log`, the realtime daemon's to
  `/var/log/flarum-realtime.log`. `flarum-logs` prints the end of each.
- Flarum caches its compiled assets, the TextFormatter parser and renderer configuration, and what extenders
  register. After changing anything in `js/`, a formatter configurator, an `extend.php` or a service provider,
  run `flarum cache:clear` (through the `flarum` wrapper, never as root, or rendering breaks on file permissions).
  After adding a migration, run `flarum migrate`. The realtime daemon is a long-running process that keeps the
  code it booted with: after a PHP change that it uses (anything under `extensions/realtime`, or broadcast
  registrations), restart it with `pkill -f 'flarum realtime:serve'` (it comes back within seconds), and expect
  writes made while it restarts to fail.
- The PHPUnit suites run offline through each package's Composer scripts, on any of the drivers Flarum supports.
  `flarum-db <mariadb|pgsql|sqlite> <command>` points a command at that driver's test database, for example:
  `cd /src/framework/core && flarum-db pgsql composer test:setup && flarum-db pgsql composer test:integration:filter -- <pattern>`
  (the same in `extensions/<name>`). Run `test:setup` once per driver and package. An integration test in the
  package's `tests/integration` is the preferred reproducer; the existing tests show how to create users, groups and
  permissions with `prepareDatabase()` and send requests as a given user.
- Flarum supports MySQL, MariaDB, PostgreSQL and SQLite, and some code paths differ between them: fulltext search,
  JSON columns, raw SQL expressions, case-sensitivity and collation, and migrations. When a finding or a patch
  touches SQL, check it on `mariadb` and `pgsql`, and on `sqlite` where the code has SQLite-specific branches.
  MySQL itself is not in the image; MariaDB stands in for it, so say when a finding may differ on MySQL. SQLite tests
  never run foreign-key actions (`ON DELETE CASCADE`, `SET NULL`), which the other drivers do: confirm anything that
  depends on deletes or orphaned rows on MariaDB or PostgreSQL.
- JS: `cd /src/extensions/<name>/js && yarn build` (or `framework/core/js`) rebuilds a package's bundle offline;
  then run `flarum cache:clear`.

## How you rate severity

Give a CVSS 4.0 vector with every report, for example
`CVSS:4.0/AV:N/AC:L/AT:N/PR:L/UI:N/VC:H/VI:H/VA:H/SC:N/SI:N/SA:N`, and the severity band it gives (Low, Medium, High,
Critical). Use `E:U` for a new finding with a private reproducer. Set PR to the least privileged account that can
exploit it: `N` for a guest, `L` for a member or a moderator, `H` for an admin.

Rules of thumb, from Flarum's own past advisories:

- Account takeover, SQL injection or code execution by a guest: Critical.
- A member making themselves admin, or taking over another account: High.
- Stored XSS a member can plant that runs for other users who only read or click normally: High.
- Reading restricted-tag discussions, private messages, unapproved or hidden content, or another user's email
  address or IP addresses, as a member or guest: Medium, High if it exposes such content in bulk.
- Changing or deleting content the actor has no permission for: Medium or High, depending on what can be changed.
- Open redirects, CSRF on low-impact actions, and information leaks that reveal only that something exists:
  Low.
- A denial of service from one cheap request that makes the server do disproportionate work: Low or Medium.
  Volumetric DoS and brute force without a bypass of the existing throttling are out of scope.
- Anything that needs an admin account (`PR:H`): report it only when it reaches beyond what admins can already do
  (see the trust model), give its CVSS vector as usual, and set the report's severity to Low whatever the vector
  scores: Flarum treats these as hardening.

## Anything to leave alone

- Version disclosure, missing or weak security headers, listing and searching users through `GET /api/users`
  where the `searchUsers` permission allows it (members have it by default), public profiles where `viewForum`
  allows them, and username or email existence checks during registration.
- Self-XSS (content only the attacker sees), XSS through admin-only settings, and anything that needs the admin's
  custom HTML, LESS or extension installation.
- Findings that depend on turning on debug mode, other `config.php` changes, or a misconfigured web server.
- Behaviour that is configurable and matches how the admin configured it, for example a permission granted to
  guests on purpose.
