# Account flagging prototype

This private preparation extends the existing MIT-licensed `flarum/flags` package in the official Flarum 2.x monorepo. It is based on framework commit `f5c80f05308d2845c1a738882e5513bd77936c25`, checked October 2, 2026.

The proposal at https://discuss.flarum.org/d/31895-introducing-user-flags requests a profile reporting control, a programmatic extender, and account reports in the existing moderation dropdown. The current prototype implements those three behaviors. Staff has not confirmed a bounty reservation, preferred delivery route, target version, or acceptance. Preparing code does not create a payment entitlement or a delivery commitment.

## Behavior

Members with `user.flag` can report another visible account from its profile controls. The existing `flarum-flags.can_flag_own` setting controls self-reporting for both posts and accounts. Account reports use the existing reasons/detail form and are private to actors with global `user.viewFlags` permission. A tag-specific or ordinary discussion moderation permission does not grant account-report access.

The `flags` JSON:API resource accepts exactly one `post` or `targetUser` relationship. `targetUser` is the reported account; the existing `user` relationship is the reporter. The server supplies the reporter and manual report type. Existing post reports keep their former routes and permissions. Account reports can be reviewed from the moderator dropdown or profile and dismissed together using `DELETE /api/users/{id}/flags`.

The migration makes `post_id` nullable, adds a nullable indexed `target_user_id` with a cascading foreign key, and adds member/moderator defaults for the new permissions. Downgrading discards account reports before restoring the old required-post schema; existing post reports survive.

Manual account reports and programmatic report types have separate identities. Sequential repeated reports by the same actor/type/target reuse the unresolved record. Concurrent submissions are not protected by a database uniqueness constraint; simultaneous requests can still produce duplicates. The moderation list groups by target and the detail modal presents all its reports.

## Programmatic use

Trusted server extensions can resolve `Flarum\Flags\UserFlagger` from the container:

```php
$flagger->flag($user, 'my-extension', 'spam', 'Spam detected in the profile');
```

A null actor denotes a trusted server extension. Passing an actor enforces registration, target visibility, account-report permission, and the self-reporting setting. The public event-driven extender can be registered in another extension's `extend.php`:

```php
(new \Flarum\Flags\Extend\UserFlags())
    ->on(
        MyProfileCheckedEvent::class,
        fn ($event) => $event->user, // return null to skip
        'spam',
        fn ($event) => $event->moderatorExplanation,
    )
```

Reports created by that extender use the calling extension's ID as their type. Account creation/dismissal emit `UserFlagCreated`/`UserFlagsDeleting` events separately from post flag events. This protects existing consumers that dereference a post. Audit integration records `user.flagged` and `user.dismissed_flags`. Account flag realtime broadcasts are not implemented; post flag broadcasts remain unchanged and account state refreshes through the ordinary API.

## Verification

Run from the monorepo after `composer install` and the normal JavaScript workspace dependency installation. The framework currently requires PHP 8.3 or newer.

```text
cd extensions/flags
php tests/integration/setup.php
php ../../vendor/phpunit/phpunit/phpunit -c tests/phpunit.integration.xml
cd js
npm test -- --runInBand
npm run check-typings
npm run build
```

The backend suite covers existing post flags plus exact target validation, account authorization and visibility, reporter privacy and inverse relationship hydration, private relationships, hidden targets, type isolation, event deduplication, counts, target-specific dismissal, audit logging, and a real SQLite upgrade/cascade/downgrade. Frontend tests render actual Flarum components/models using JSDOM, with network requests mocked at the request boundary. They cover mixed target IDs, missing relations, profile controls, and successful/failed dismissal without altering unrelated post reports.

An isolated local browser fixture also exercised the real Flarum application, HTTP API, SQLite database and compiled frontend with synthetic accounts: account report submission, mixed post/account moderation, reporter attribution and account-only dismissal. The fixture uses a private monorepo package discovery adapter; no request or component mock is used in this browser flow. This demonstrates that local path, without establishing production performance, multi-database migration behavior or staff acceptance. The inherited non-PostgreSQL grouping selects an unspecified representative report when a target has several reports; all reports remain available in its review modal. PostgreSQL and MySQL/MariaDB have not been exercised locally.

## License

The extension and these changes remain under the MIT license supplied in `LICENSE`. Existing Flarum authorship and notices are preserved. New code does not incorporate another applicant's unpublished implementation.
