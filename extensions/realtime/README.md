![](https://extiverse.com/extension/flarum-com/realtime/open-graph-image)

Realtime provides a self-hosted alternative to Pusher with far more features and an active development roadmap. It offers realtime updates of activity on your forum, not just for members but also for guests.

## Features

- Auto update of index (while keeping permissions & subscription states in mind).
- Auto update of notifications (likes, replies, but also for flags).
- New posts pushed into discussions.
- Typing indicator showing the amount of people currently working on a reply in a discussion.

## Requirements

Realtime contains a **service you will need to install** on your hosting environment, similar to the database service (MySQL) or web service (Apache, Nginx). As such you need your own virtual machine, droplet or an environment that allows you to configure scripts to run continuously. In addition, you will need to **run a queue**, we highly recommend the redis queue in combination with realtime.

For this reason this extension isn't likely applicable to anyone hosted on shared hosting environments.

## Premium

This extension requires an active subscription from [flarum.org](https://flarum.org/extension/flarum-com/realtime). 

Due to the complexity of this extension we are forced to publish this extension as a paid one. Development and maintenance of these kind of extensions take a massive amount of time. In order to still make Realtime available to as many people as possible, we offer a plan suitable to those who are able to set everything up themselves and a plan for those in need of assistance.

**Important!** There are two tiers of plans for Realtime:

1. Entry edition, [view plan](https://flarum.org/extension/flarum-com/realtime?key=realtime-entry-edition):
   - Low cost
   - Only for non-profit communities
   - No installation assistance
   - No expert support
   - Single payment, perpetual usage
2. Advanced edition, [view plan](https://flarum.org/extension/flarum-com/realtime?key=realtime-advanced-edition):
   - For non-profit and for-profit communities
   - Installation assistance up to two hours
   - Expert support through email and/or discord
   - Yearly subscription

In case you need installation assistance from us directly while on the Entry edition, we will require you to upgrade. Installation assistance and expert support cannot and will not be given on the Entry edition.

Once your subscription is active you can follow the instructions on the [subscriptions page](https://flarum.org/dashboard/subscriptions) to configure composer. Once completed you can run the following command for installation:

```bash
composer remove flarum/pusher
composer require blomstra/realtime:"*"
```

For updates:

```bash
composer require blomstra/realtime:"*"
php flarum migrate
php flarum cache:clear
```

> Managed Flarum communities that we host on our hosting platform ([Blomstra](https://flarum.org/hosting)) have access to all premium extensions by Flarum BV without additional cost.

Enable the extension inside the admin area and follow Set up instructions.

### Set up

**Method a) use the defaults**

No action needed. Your websocket will re-use the configuration of your existing forum.

**Method b) the `config.php`**

Create a key `websocket` and override any of the configuration items:

```php
return [
    // ..
    'websocket' => [
    
    ] 
];
```

#### Configuration

All options:

- `server-*`, these options are used for the daemon itself, it specifies what ip/host and port to listen to.
  - `server-host` / default(`0.0.0.0`) ; on which host the realtime daemon listens
  - `server-port` / default(`6001`) ; on which port the realtime daemon listens
- `js-client-*`, these are used by the Flarum forum frontend (javascript) to connect to the Realtime websocket daemon.
  - `js-client-host` / default(config url) ; the host on which the Flarum forum will connect to the websocket server
  - `js-client-port` / default(`6001`) ; the port on which the Flarum forum will connect to the websocket server
  - `js-client-secure` / default(config url is https then yes, otherwise no) ; whether the Flarum forum will encrypt its connection to the websocket server
- `php-client-*`, when the Flarum backend wants to dispatch events to all users connected to Realtime it will push data to the Realtime daemon with these settings.
  - `php-client-host` / default(config url) ; the host to which the Flarum backend will send events
  - `php-client-port` / default(`6001`) ; the port to which the Flarum backend will send events
  - `php-client-secure` / default(config url is https then yes, otherwise no) ; whether the Flarum backend will communicate encrypted with the Realtime daemon
  - `php-client-timeout` / default(`3`) ; when the Flarum backend sends events to the Realtime daemon it will timeout in this number of seconds
- `app-*`, these define the authorization between server, javascript and daemon
  - `app-key` / default(hashed version of config url) ; the publicly available key to authenticate with against the Realtime daemon
  - `app-secret` / default(hashed version of database password) ; the secret password to grant access to private channels and to dispatch events to the Realtime daemon with
- `max-connections` / default(`1000`), the maximum number of allowed concurrent connections. Reduce this number to lower the strain on your server in case you notice the Realtime daemon being a problem.


### Run the websocket server

For the websocket to run you will need to run its server. To do so for **testing purposes**, use:

```bash
php flarum realtime:serve -vvv --debug
```

This will boot the server and throw any debug information it can. This will keep the server running for as long as you keep this tab/window open.

> The webservice will only run for as long as you keep your window open or your pc on using the above command. Using the realtime service in production requires setting it up as a daemon. See below for instructions for supervisor or systemd.

#### Daemon with supervisor

In production, you will need to set the websocket server to run continuously and restart when it errors. You can use a tool like supervisord for that.

```bash
# On Debian / Ubuntu
apt install supervisor

# On Red Hat / CentOS
yum install supervisor
systemctl enable supervisord
```

Now create a new file inside the `/etc/supervisor/conf.d` directory called `realtime.conf` with:

- `/var/www/flarum` being the path to your Flarum installation, update this under the `command` line.
- `www-data` being the web user that runs your Flarum forum in apache, nginx or other web server software. Update under the `user` line.

```
[program:realtime]
command=/usr/bin/php flarum realtime:serve
directory=/var/www/flarum/
numprocs=1
autostart=true
autorestart=true
user=www-data
stdout_logfile=/var/www/flarum/storage/logs/realtime.log
stderr_logfile=/var/www/flarum/storage/logs/realtime-error.log
```

Now read the configuration file and start the service:

```bash
sudo supervisorctl update
```

Check whether the program is running:

```bash
sudo supervisorctl status
```

More information about supervisor and its commands can be found in their [documentation](http://supervisord.org/running.html#running-supervisorctl).

#### Daemon with systemd

Create a file with vim, vi or another editor of your choice at `/etc/systemd/system/flarum-realtime.service` with these contents:

```
[Unit]
Description=flarum-realtime
StartLimitIntervalSec=0

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/flarum
ExecStart=/usr/bin/php flarum realtime:serve
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

Make sure to update the `WorkingDirectory` to match the directory where you installed Flarum. Update `ExecStart` to your version of php, you can use `whereis php` or `whereis php80` to seek the path for each php version. Update the `User` to the user your site runs as.

Now reload the systemd service to pick up the service:

```
sudo systemctl daemon-reload
```

Start your service:

```
sudo systemctl start flarum-realtime.service
```

To understand whether it runs:

```
sudo systemctl status flarum-realtime.service
```

Now make sure to automatically start the service on reboots:

```
sudo systemctl enable flarum-realtime.service
```

### Auto restarting

The daemon will halt itself within 10 seconds when it identifies any change in 
your extensions. If you en- or disable an extension the daemon will stop. 
If properly set up this will allow the
daemon to understand your Flarum changes and operate with the enabled extensions.

You can disable this feature by setting the `--ignore-extension-toggles` flag on the daemon:

```bash
php flarum realtime:serve --ignore-extension-toggles
```

### Manually restarting

In case you want to force a restart within 10 seconds, for instance when using CI/CD, you can use the `realtime:halt` command:

```bash
php flarum realtime:halt
```

### Running encrypted

If you want to run the websocket server encrypted, the easiest way is to proxy the port with your webserver software (apache, nginx).

For **nginx** you can use the provided nginx configuration. Make sure to put the realtime nginx include before your flarum nginx include. Make sure to put both of these at the end of your server block:

```nginx
server {
  # your php matching instructions
  
  include /var/www/flarum/vendor/blomstra/realtime/.nginx.conf;
	include /var/www/flarum/.nginx.conf;
}
```

> We don't have examples for other webservers yet, but we'll gladly assist you with the installation on the Advanced edition.

**Optional:** to reduce overhead on your community from Flarum trying to interact with a remote url for sending realtime events, we can configure Flarum to use the current server as a target. To do so we can update our Flarum `config.php` like this:

```php
return [
    // .. some other instructions
    
    // Make sure the Realtime php client uses localhost instead of the domain
    // to reduce latency.
    'websocket' => [
        'php-client-host' => 'localhost'
    ]
];
```


### FAQ

*Is Realtime proven?*
The official Flarum community (discuss.flarum.org) has benefited from the features Realtime brings since August 2021. It has also been running on the Blomstra hosting platform since February 2021, most of our managed communities have it enabled. 

*How many concurrent users does Realtime support?*
Resource usage of the websocket is really low. You should be able to serve thousands simultaneous users with only 1 cpu and 1 GB of memory dedicated to the process. Once we are able to provide a better indication from usage at scale, we will update this FAQ item. As a general rule of thumb it's always better to oversize your realtime daemon resource limitations and then - based on experience - reduce them to meet actual resource cost.

*SendTriggerJob fails/is killed.*
In case the queue job `SendTriggerJob` fails every single time, make sure to increase the timeout of the queue worker. The default timeout is `60`, also when you leave out the flag, so try running it with `360` like so: `php flarum queue:work --timeout=360`. Read the documentation of your queue driver to understand how to daemonize this command, for testing you can run it in your window with the additional `-vvv` flag for verbose logging.

*I have another question.*
Reach out to us via https://flarum.org/contact/premium-support. We will get back to you as soon as we can. If you have a running subscription please mention when you started your plan and/or which plan you are on. Always add sufficient information when reporting errors. We prefer errors being reported here, but understand that sometimes you can't.

---

- Flarum BV is the commercial companion to the Flarum Foundation.
- https://flarum.org
- https://support.on-flarum.com/t/ext-realtime
