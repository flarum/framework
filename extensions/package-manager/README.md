# Extension Manager

The extension manager is a tool that allows you to easily install and manage extensions. It runs [composer](https://getcomposer.org/) under the hood.

## Security

If admin access is given to untrustworthy users, they can install malicious extensions. Please be careful.

This extension is optional and can be removed for those who prefer to manually manage installs and updates through the command line interface.

## Troubleshooting

If you have many extensions installed, you may run into memory issues when using the extension manager. If this happens, you can use an asynchronous queue that will run the extension manager in the background.

* Simple database queue guide: https://discuss.flarum.org/d/28151-database-queue-the-simplest-queue-even-for-shared-hosting
* (Advanced) Redis queue: https://discuss.flarum.org/d/21873-redis-sessions-cache-queues

You can find detailed logs on the extension manager operations in the `storage/logs/composer` directory. Please include the latest log file when reporting issues in the [Flarum support forum](https://discuss.flarum.org/t/support).
