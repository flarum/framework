# Extension Manager

Update, install and remove Flarum extensions — and Flarum itself — on any host,
including shared hosting.

**Nothing is deleted, and every step is written down before it is taken.** An
interrupted update costs you progress, never your site.

---

Composer ships with the Extension Manager, so the host does not need it
installed. Where PHP may start a subprocess (`proc_open`), Composer runs in its
own process; where it may not, as on most shared hosting, it runs inside the web
request instead (see below). **Extension Manager → This host** says which.

## Security

If admin access is given to untrustworthy users, they can install malicious
extensions. Please be careful.

This extension is optional and can be removed for those who prefer to manage
installs and updates through the command line.

## What it does

**Updates extensions.** One at a time, or everything with a newer version in one
resolve — which matters, because two extensions can each have an update and
still be uninstallable side by side. Asking about them together is the only way
to find that out before anything moves.

**Installs new ones.** Search Packagist from the admin page, and every result
says whether it has a release that works with **the Flarum you are actually
running** — before you press anything, rather than as a constraint error at the
end of a long wait.

**Removes them.** The same operation as any other, so the files go to the trash
rather than being deleted, and an uninstall is as reversible as an update. Your
settings and any data the extension stored are left alone: reinstalling puts you
back exactly where you were.

**Updates Flarum itself** — and first tells you, per extension and by name, which
of yours have a release that works with the version you are moving to. Anything
that would block the upgrade is listed at the top, and the button stays disabled
until it is resolved. Extensions that need to move are updated in the same
resolve as core, because updating core alone leaves Composer refusing.

**Tells you what your host will allow, before you press anything.** Memory,
execution limit, whether Composer can be run at all, whether PHP will even notice
the new files, and how much disk is free — each with what it *means*, not just
what it is.

**Checks for newer versions** on a schedule, and puts a count on the dashboard.
The wording is deliberate: it says a newer version *exists*, never that an update
*is available*. Only a real resolve knows the second, and a badge that overstates
itself is one people learn to ignore.

**Finishes the job.** Moving the files is the easy half. After a change lands,
The Extension Manager registers it with Composer, runs any migrations it brought, publishes
its assets, clears the caches and rebuilds the post formatter, waits for PHP to
re-read the new files, and then asks the site whether it is still answering. Only
then does it say the update is done.

That last pair matters more than it sounds. A tool that reports success the
moment Composer exits is reporting on a site that may still be executing the
previous version of the files for up to a minute — and if the update broke
something, nobody finds out from the tool.

**Survives being run as root.** `php flarum …` typed as root writes root-owned
files into `storage/` that the web user can never replace — removing a file needs
write permission on its *directory*, and that is root's too. The formatter cache
is the usual casualty: posts stop rendering site-wide while `/` carries on
answering 200, so nobody notices for hours. The Extension Manager ships a guard for the
console entry that ends the whole class of it — see below.

## Compared with the previous Extension Manager

The previous Extension Manager does run migrations, publish assets and clear caches
— but that work is wired to a **core** update. Installing or updating an
*extension* runs the Composer command and stops there.

| after the files land | the previous Extension Manager | the Extension Manager |
|---|---|---|
| install an extension | core migrates and publishes assets when you *enable* it | same, plus everything below |
| **update an extension** | **nothing** | migrations, assets, caches, formatter, code cache, health check |
| update Flarum itself | migrate, assets, cache clear | all of the above |
| PHP's compiled-code cache | never touched | waited for, then cleared |
| is the site still up afterwards | not checked | checked, and rolled back if not |

So the gap is on **updates**, not installs. Update an already-enabled extension
that ships a new migration or new JS, and the schema is left behind the code and
the browser keeps serving the old assets, with nothing saying so.

## Hosts without proc_open: "Runs without separate processes"

When the website's PHP cannot start another program (`proc_open` in `disable_functions`, or no command-line PHP to start), the Extension Manager runs Composer inside the web request. That is safe here because Composer never touches `vendor/`: it only works out the new `composer.lock`, and the Extension Manager downloads and swaps the packages itself, journalled, exactly as it does everywhere else. The limits:

- Composer shares the request's memory and time. The Extension Manager lifts both while Composer runs where the host allows, and puts them back afterwards.
- The first update on a cold Composer cache is slow. Measured on a forum with 346 packages and 22 private GitHub repositories: about 145 seconds cold, 27 warm. If the host or the web server cuts the request short, nothing is changed: composer.json and composer.lock are put back, and the next poll of the Extension Manager page tries again (up to six times), each time with more of Composer's cache already downloaded.
- Git cannot run, so a Git source Composer would need git for is refused before anything changes, with the fix named: add a GitHub token under **Sources** so Composer reads the repository through GitHub's API, or serve the package from a Composer repository. Repositories marked `"no-api": true` always need git.
- Migrations, asset publishing and cache clearing run through Flarum's own console inside a fresh request after the swap, once PHP's compiled-code cache has let go of the old files. A queue worker leaves those steps to the admin page, so keep it open until the update finishes.

## Troubleshooting: the Extension Manager says it can't run Composer in its own process

Open **Extension Manager → This host**. It names what is in the way, and what to change. None of these stop updates any more; fixing them moves Composer back into its own process.

**proc_open is disabled.** The website's PHP lists `proc_open` in `disable_functions`, so it cannot start any other program. Ask your host to remove it for this site. The row names the php.ini the setting comes from.

**No command-line PHP was found.** Composer runs under PHP's command-line program, and the Extension Manager looks for the one that matches the PHP your site runs on. The usual reason it cannot find one on a hosting panel is `open_basedir`: the website's PHP is only allowed to look inside your site's own folders, so the command-line PHP is invisible to it even though it is installed. The Extension Manager tries the likely paths anyway, and when none answers it shows the path open_basedir hid and the fix for your panel:

- **Plesk:** Websites & Domains → your domain → PHP Settings → `open_basedir`. Add `:/opt/plesk/php/8.5/bin/` to the end of the value (use the version your site runs), save, and reload the This host tab.
- **cPanel:** MultiPHP INI Editor → choose the domain → `open_basedir`. Add `:/opt/cpanel/ea-php85/root/usr/bin/` (your version), save, and reload. Some cPanel hosts only let support change it.
- **Anything else:** add the directory to `open_basedir` in the site's php.ini or PHP-FPM pool (`php_admin_value[open_basedir]`) and restart PHP-FPM.

If you know where the command-line PHP is, you can also enter its full path under **Command-line PHP path** on the same tab. The Extension Manager runs it before saving and refuses anything that is not the command-line PHP, such as `php-fpm`.

**A command-line PHP was found but fails to run.** The row shows the error it printed, often a PHP extension that fails to load. Ask your host, with that error.

Testing over SSH does not settle any of this. The PHP you run over SSH is configured separately from the PHP that runs your website, so `proc_open` and `open_basedir` can be fine there and still block the site.

## How it works

### Applying a change is two renames, never a delete

```
vendor/<pkg>   →  trash/<pkg>@<version>     the old one is kept
staging/<pkg>  →  vendor/<pkg>              the new one arrives
```

Both are `rename()`, so each is atomic. The package is absent between them and
only between them — microseconds, for one package. Nothing is deleted during an
apply at all: the old version waits in the trash, which is what makes rollback
possible long after the fact.

### The journal is written before the act, and flushed to disk

A crash therefore leaves evidence of an *intention*, which is recoverable. A
journal written afterwards would leave a changed tree with no record of what
changed, which is not. Rollback replays it backwards, and its cost is
proportional to what changed rather than to the size of your `vendor/` — so it
works on a host with a disk quota where copying the tree would not.

### The trash is tidied, never what a rollback needs

Only the latest update can be rolled back, so the copies its journal names are
never removed. Neither is anything belonging to an update still in progress,
the most recent finished one, or anything that changed in the last day. Older
copies are kept for the last 30 days or the last 5 updates, whichever keeps
more (both are settings on the **This host** tab). What falls outside that is
removed after every update and nightly: copies only an older update refers to,
the `.rolledback` versions a rollback moved aside, and anything no update
refers to at all. Each update's summary is kept; its journal and staging go
with its copies.

```bash
php flarum extension-manager:prune --dry-run   # what would go, and how much room it frees
php flarum extension-manager:prune
```

The removal never follows a symlink and refuses any path that is not directly
inside the trash.

### Nothing loops

One request does exactly one unit of work and returns. Progress is a function of
how many times something calls the step endpoint — the admin page polling, a cron
tick, or a queue worker, interchangeably, and any of them can pick up a run
another started. A host that cuts every request at thirty seconds can therefore
finish an update that takes ten minutes, which no amount of making the update
faster would have achieved.

If you have a queue worker, it carries the work on when you close the tab. If you
do not, the page does it. **The queue is never what makes an update work** — that
distinction is the whole design.

## What it deliberately does not do

**It will not touch an extension installed from a local path.** If
`vendor/you/thing` is a symlink into a checkout on the server — how extension
developers work — replacing it would leave your forum running a downloaded copy
while you carry on editing a directory nothing reads. Those are labelled *local
checkout* and offered no buttons.

**It does not flip symlinks between prepared slots.** This was planned, and
measurement killed it: `opcache.revalidate_path` is off by default, so PHP
resolves a symlink once and caches the result, and `realpath_cache_ttl` holds the
old target for two minutes more. A slot flip is atomic on disk and invisible to
PHP — it would trade a microsecond where a package is missing for minutes of
quietly serving the old code. Replacing a directory keeps the path constant, so
the ordinary timestamp check picks it up in seconds.

**It does not delete your data when you remove an extension.** Tables and
settings stay. This matches the previous Extension Manager, and it is the reversible choice.

## Tests

```bash
composer test:unit
composer test:setup && composer test:integration
```

The unit suite that matters is `CrashRecoveryTest`. It runs a real apply in a
subprocess and **SIGKILLs it at every step in the sequence** (before anything is
touched, after the old version is stashed but before the new one lands, after it
lands but before the journal records it, and after the record is complete),
then rolls back and asserts the tree is byte-for-byte what it was. A thrown
exception would be a weaker test: it unwinds the stack and runs destructors,
which a host killing an overrunning request does not.

`ResumabilityTest` kills at *random* points, repeatedly, until a run completes,
a better model of a real host, where the cut lands wherever the clock happens to
be. One case kills in the single gap that matters, between doing an item and
recording it; that item is redone on resume, and the test asserts the repeat
rather than pretending it cannot happen.

