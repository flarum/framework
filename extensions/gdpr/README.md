# GDPR or PII management

This extension allows users increasing control over their data.

### Requirements

- `flarum/core` - `v2.0` or higher
- `PHP` - `8.2` or higher

### Installation or update

Install manually with composer:

```sh
composer require flarum/gdpr:^2.0.0
```

### Use

All forum users now have a `Personal Data` section within their account settings page:

![image](https://github.com/flarum/gdpr/assets/16573496/4e469956-709f-4ba3-a5fe-d3fcb0401b73)

From here, users may self-service export their data from the forum, or start an erasure request. Erasure requests are queued up for admins/moderators to process. Any unprocessed requests that are still pending after 30 days will be processed automatically using the configured default method (Deletion or Anonymization).

#### Specifying which queue to use
If your forum runs multiple queues, ie `low` and `high`, you may specify which queue jobs for this extension are run on in your skeleton's `extend.php` file:

```php
Flarum\Gdpr\Jobs\GdprJob::$onQueue = 'low';

return [
    ... your current extenders,
];
```

### For developers

You can easily register a new Data type, remove an existing Data type, or exclude specific columns from the user table during export by leveraging the `Flarum\Gdpr\Extend\UserData` extender. Ensure that you wrap the GDPR extender in a conditional extend, so that forum owners can choose if they want to enable GDPR functionality or not. This functionality requires `flarum/core` `v2.0` or higher, so that should be set as your extension's minimum requirement.

#### Registering a new Data Type:

Your data type class should implement the `Flarum\Gdpr\Contracts\DataType`:
```php
<?php

use Flarum\Gdpr\Extend\UserData;
use Flarum\Extend;

return [
    (new Extend\Conditional())
        ->whenExtensionEnabled('flarum-gdpr', fn () => [
            (new UserData())
                ->addType(Your\Own\DataType::class),

            ... other conditional extenders as required ...
        ]),
];
```

The implementation you create needs a export method, it will receive a ZipArchive resource.
You can use that to add any strings or actual files to the archive. Make sure to properly
name the file and always prefix it with your extension slug (flarum-something-filename).

#### Removing a Data Type:
If for any reason you want to exclude a certain DataType from the export:
```php
use Flarum\Gdpr\Extend\UserData;
use Flarum\Extend;

return [
    (new Extend\Conditional())
        ->whenExtensionEnabled('flarum-gdpr', fn () => [
            (new UserData())
                ->removeType(Your\Own\DataType::class),

            ... other conditional extenders as required ...
        ]),
];
```

#### Redacting specific user table columns from exports:

By default, the `User` data type exports all columns from the `users` table except `id`, `password`, `groups`, and `anonymized`. If your extension adds a column to the `users` table that should not appear in the export (e.g. a sensitive internal token), you can register it via `removeUserColumns()`.

The column's value will be **set to `null` on the in-memory user object** before the export ZIP is generated — the column still appears in `user.json` but with a `null` value. This is visible in the admin GDPR overview under "User Table Data".

```php
use Flarum\Gdpr\Extend\UserData;

return [
    (new Extend\Conditional())
        ->whenExtensionEnabled('flarum-gdpr', fn () => [
            (new UserData())
                ->removeUserColumns(['column1', 'column2']),

            ... other conditional extenders as required ...
        ]),
];
```

#### PII fields and anonymized contexts

##### What is an "anonymized context"?

Some extensions need to share Flarum data with external systems — for example, publishing events to a message broker, syncing to a search index, or sending webhooks. In these scenarios there are typically two audiences:

- **Full-data consumers** — internal systems that are authorised to process PII (e.g. a private analytics pipeline).
- **Anonymized consumers** — systems where PII must not appear (e.g. a public event stream, a third-party integration, or any consumer that doesn't need identifying information).

An "anonymized context" is any such output where PII keys must be redacted before the data leaves the application. For example, [glowingblue/rabbit-dispatcher](https://github.com/glowingblue/rabbit-dispatcher) publishes Flarum events to RabbitMQ on two exchanges simultaneously: one with full payloads, and one with all PII keys replaced by `[redacted]`. The PII key list comes from `flarum/gdpr` so that every registered extension's sensitive fields are automatically covered.

The GDPR admin page ("User Table Data" section) shows which fields are currently registered as PII, giving admins visibility into what will be redacted.

##### Declaring PII fields on your data type

If your extension stores personally identifiable information, declare which keys are PII by overriding `piiFields()` on your data type class. This is the preferred approach — the declaration lives alongside your `anonymize()` logic, and the keys are automatically included in the PII registry as soon as your type is registered.

```php
use Flarum\Gdpr\Data\Type;

class MyData extends Type
{
    public static function piiFields(): array
    {
        return ['custom_field', 'another_pii_field'];
    }

    // ... export(), anonymize(), delete() ...
}
```

##### Declaring PII fields without a data type

If your extension stores PII in a field that doesn't belong to any registered data type (e.g. a column on a model you don't export via GDPR), register the keys via the `UserData` extender instead:

```php
use Flarum\Gdpr\Extend\UserData;

return [
    (new Extend\Conditional())
        ->whenExtensionEnabled('flarum-gdpr', fn () => [
            (new UserData())
                ->addPiiKeysForSerialization(['custom_field', 'another_pii_field']),
        ]),
];
```

##### Building an anonymized context (consuming the PII list)

If you are building an extension that serializes Flarum data for an external system and want to support PII redaction, resolve the PII key list from `DataProcessor` at runtime. Always check whether `flarum-gdpr` is enabled first and provide your own fallback for when it is not:

```php
use Flarum\Extension\ExtensionManager;
use Flarum\Gdpr\DataProcessor;

$extensions = resolve(ExtensionManager::class);

if ($extensions->isEnabled('flarum-gdpr')) {
    $piiKeys = resolve(DataProcessor::class)->getPiiKeysForSerialization();
} else {
    // Fallback covering common fields — used when flarum/gdpr is not installed.
    $piiKeys = ['email', 'username', 'ip_address', 'last_ip_address'];
}

// Recursively redact PII from your serialized payload before sending externally.
$anonymizedPayload = redactKeys($payload, $piiKeys);
```

`getPiiKeysForSerialization()` aggregates fields declared by all registered data types (via `piiFields()`) plus any extras registered via `addPiiKeysForSerialization()`. This means every enabled extension that participates in the GDPR registry contributes its PII fields automatically — your consumer code doesn't need to know about them individually.

### Flarum extensions

These are the known extensions which offer GDPR data integration with this extension. Don't see a required extension listed? Contact the author to request it

- [2FA](https://github.com/imorland/flarum-ext-twofactor), since `2.0`
- [Boring Avatars](https://github.com/imorland/flarum-ext-boring-avatars), since `2.0`
- [FoF Ban IPs](https://github.com/FriendsOfFlarum/ban-ips), since `2.0`
- [FoF Drafts](https://github.com/FriendsOfFlarum/drafts), since `2.0`
- [FoF Follow Tags](https://github.com/FriendsOfFlarum/follow-tags), since `2.0`
- [FoF Terms](https://github.com/FriendsOfFlarum/terms), since `2.0`
- [FoF Upload](https://github.com/FriendsOfFlarum/upload), since `2.0`
- [FoF User Bio](https://github.com/FriendsOfFlarum/user-bio), since `2.0`
- [Follow Users](https://github.com/imorland/follow-users), since `2.0`

### FAQ & Recommendations

- Generating the zip archive can be pushed to queue functionality. This is exceptionally important on larger communities and with more extensions that work with the gdpr extension to allow data exports.
