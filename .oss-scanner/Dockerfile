# Flarum 2.x (flarum/framework monorepo: core and bundled extensions), for Anthropic's OSS Scanner.
#
# /src           the flarum/framework checkout, with the monorepo's Composer and Yarn dependencies installed, so
#                each package's PHPUnit suites and JS builds run offline
# /opt/flarum    a forum built from the flarum/flarum skeleton, with every monorepo package installed from /src
#                through symlinked Composer path repositories, so PHP edits in /src reach the forum
#
# Apache (mod_rewrite, AllowOverride All, the skeleton's public/.htaccess) serves the forum at http://127.0.0.1/ on
# MariaDB, with debug mode off, as on a production install, and flarum/realtime's websocket daemon listens on
# 127.0.0.1:6001. PostgreSQL also runs, so the test suites can run on every driver Flarum supports (`flarum-db`);
# MySQL itself is not packaged for Debian, so MariaDB stands in for it. Start them all with `flarum-start`.
FROM php:8.5-apache-trixie

ENV DEBIAN_FRONTEND=noninteractive \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1 \
    COMPOSER_MEMORY_LIMIT=-1

# The base image already provides OPcache, mbstring, dom, fileinfo, openssl and tokenizer.
RUN apt-get update && apt-get install -y --no-install-recommends \
      ca-certificates git unzip curl jq procps mariadb-server mariadb-client postgresql libpq-dev \
      libfreetype6-dev libjpeg62-turbo-dev libpng-dev libwebp-dev libzip-dev libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" gd zip intl pdo_mysql pdo_pgsql exif \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY --from=node:24-trixie-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:24-trixie-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx \
    && npm install -g yarn@1

# OPcache picks up edited files immediately so a patch can be checked straight away.
RUN { echo 'memory_limit=256M'; \
      echo 'upload_max_filesize=8M'; \
      echo 'post_max_size=8M'; \
      echo 'error_reporting=E_ALL'; \
      echo 'opcache.validate_timestamps=1'; \
      echo 'opcache.revalidate_freq=0'; \
    } > /usr/local/etc/php/conf.d/oss-scanner.ini \
    && mkdir -p /var/log/php && touch /var/log/php/error.log && chown -R www-data:www-data /var/log/php

# Loopback only: the seeded accounts have published passwords. The forum's errors go to files rather than
# responses (as in production) or stderr (gone once the shell that ran `apachectl start` exits); the CLI keeps PHP's
# defaults so tests and reproducers show their errors.
RUN a2enmod rewrite headers \
    && sed -ri 's!^Listen 80$!Listen 127.0.0.1:80!' /etc/apache2/ports.conf \
    && sed -ri -e 's!/var/www/html!/opt/flarum/public!g' \
         -e 's!\$\{APACHE_LOG_DIR\}/error.log!${APACHE_LOG_DIR}/flarum-error.log!' \
         -e 's!\$\{APACHE_LOG_DIR\}/access.log!${APACHE_LOG_DIR}/flarum-access.log!' \
         /etc/apache2/sites-available/000-default.conf \
    && sed -ri 's!^ErrorLog .*!ErrorLog ${APACHE_LOG_DIR}/flarum-error.log!' /etc/apache2/apache2.conf \
    && printf '%s\n' \
         '<Directory /opt/flarum/public>' \
         '  Options -MultiViews +FollowSymLinks' \
         '  AllowOverride All' \
         '  Require all granted' \
         '</Directory>' \
         'php_admin_flag display_errors off' \
         'php_admin_flag log_errors on' \
         'php_admin_value error_log /var/log/php/error.log' \
         'ServerName 127.0.0.1' \
         > /etc/apache2/conf-available/flarum.conf \
    && a2enconf flarum

# scanner contract: the checkout lives inside the image, at /src
COPY . /src
WORKDIR /src

COPY .oss-scanner/bin/ /usr/local/bin/
RUN chmod +x /usr/local/bin/flarum /usr/local/bin/flarum-*

# Without the root version, a checkout with no branch name resolves to 1.0.0 and Composer pulls a second flarum/core
# from Packagist for the tests.
RUN COMPOSER_ROOT_VERSION=dev-main composer install --no-progress \
    && composer clear-cache
RUN yarn install --frozen-lockfile --non-interactive --network-timeout 600000 \
    && yarn cache clean

# flarum/pusher is left out because flarum/realtime conflicts with it.
RUN composer create-project --no-install --no-scripts --no-progress 'flarum/flarum:^2.0' /opt/flarum \
    && alias="$(jq -r '.extra["branch-alias"]["dev-main"]' /src/framework/core/composer.json)" \
    && versions="$(for f in /src/framework/*/composer.json /src/extensions/*/composer.json /src/php-packages/*/composer.json; do \
         jq -r --arg v "$alias" '{(.name): $v}' "$f"; done | jq -s add)" \
    && require="$(jq -r 'select(.name != "flarum/pusher") | .name' /src/extensions/*/composer.json \
         | jq -R '{(.): "*"}' | jq -s add)" \
    && jq --argjson versions "$versions" --argjson require "$require" ' \
         .repositories = [ "framework", "extensions", "php-packages" | \
           { type: "path", url: "/src/\(.)/*", options: { symlink: true, versions: $versions } } ] \
         | .require += $require | ."minimum-stability" = "dev" | ."prefer-stable" = true' \
         /opt/flarum/composer.json > /tmp/composer.json \
    && mv /tmp/composer.json /opt/flarum/composer.json \
    && composer install --no-progress --working-dir=/opt/flarum \
    && composer clear-cache \
    && chown -R www-data:www-data /opt/flarum

# The installer only copies the icon fonts; `assets:publish` also publishes core's XSLT polyfill.
RUN flarum-start \
    && mariadb -e "CREATE DATABASE flarum; CREATE DATABASE flarum_test; CREATE USER 'flarum'@'%' IDENTIFIED BY 'flarum'; \
         GRANT ALL ON flarum.* TO 'flarum'@'%'; GRANT ALL ON flarum_test.* TO 'flarum'@'%';" \
    && runuser -u postgres -- psql -q -v ON_ERROR_STOP=1 \
         -c "CREATE ROLE flarum LOGIN PASSWORD 'flarum'" -c "CREATE DATABASE flarum_test OWNER flarum" \
    && ids="$(jq -r '(.packages // .)[] | select(.type == "flarum-extension") \
         | .name | split("/") | "\(.[0])-\(.[1] | gsub("flarum-ext-|flarum-"; ""))"' \
         /opt/flarum/vendor/composer/installed.json | paste -sd, -)" \
    && jq -n --arg ids "$ids" '{ \
         debug: false, baseUrl: "http://127.0.0.1", \
         databaseConfiguration: { driver: "mariadb", host: "127.0.0.1", port: 3306, database: "flarum", \
           username: "flarum", password: "flarum", prefix: "" }, \
         adminUser: { username: "admin", password: "password", email: "admin@example.com" }, \
         settings: { mail_driver: "log" }, extensions: $ids }' > /tmp/install.json \
    && flarum install --file=/tmp/install.json \
    && rm /tmp/install.json \
    && flarum assets:publish \
    && php -r '$f = "/opt/flarum/config.php"; $c = include $f; $c["websocket"] = ["server-host" => "127.0.0.1"]; \
         file_put_contents($f, "<?php return ".var_export($c, true).";\n");' \
    && flarum-start \
    && (flarum-seed || { flarum-logs; exit 1; }) \
    && flarum-stop

# A broken test setup fails the build; failing tests only warn.
RUN flarum-start \
    && (curl -fsS -o /dev/null http://127.0.0.1/ && curl -fsS -o /dev/null http://127.0.0.1/api/discussions \
          || { flarum-logs; exit 1; }) \
    && cd /src/framework/core \
    && for driver in mariadb pgsql sqlite; do \
         flarum-db "$driver" composer test:setup \
         && (flarum-db "$driver" composer test:integration:filter -- 'Flarum\\Tests\\integration\\api\\discussions\\ShowTest' \
               || echo "WARNING: core integration tests reported failures on $driver") \
         || exit 1; \
       done \
    && flarum-stop

WORKDIR /src

# The base image's default runs Apache as PID 1, which `flarum-stop` would then end along with the container.
CMD ["sleep", "infinity"]
