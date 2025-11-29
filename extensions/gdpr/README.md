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
Blomstra\Gdpr\Jobs\GdprJob::$onQueue = 'low';

return [
    ... your current extenders,
];
```

### For developers

You can easily register a new Data type, remove an existing Data type, or exclude specific columns from the user table during export by leveraging the `Flarum\Gdpr\Extend\UserData` extender. Ensure that you wrap the GDPR extender in a conditional extend, so that forum owners can choose if they want to enable GDPR functionality or not. This functionality requires `flarum/core` `v2.0` or higher, so that should be set as your extension's minimum requirement.

#### Registering a new Data Type:

Your data type class should implement the `Blomstra\Gdpr\Contracts\DataType`:
```php
<?php

use Blomstra\Gdpr\Extend\UserData;
use Blomstra\Extend;

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
use Blomstra\Gdpr\Extend\UserData;
use Blomstra\Extend;

return [
    (new Extend\Conditional())
        ->whenExtensionEnabled('flarum-gdpr', fn () => [
            (new UserData())
                ->removeType(Your\Own\DataType::class),

            ... other conditional extenders as required ...
        ]),
];
```

#### Exclude specific columns from the user table during export:
```php
use Blomstra\Gdpr\Extend\UserData;

return [
    (new Extend\Conditional())
        ->whenExtensionEnabled('flarum-gdpr', fn () => [
            (new UserData())
                ->removeUserColumn('column_name') // For a single column
                ->removeUserColumns(['column1', 'column2']), // For multiple columns

            ... other conditional extenders as required ...
        ]),
];
```
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
