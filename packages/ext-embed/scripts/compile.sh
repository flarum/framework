#!/usr/bin/env bash
base=$PWD

cd $base
composer install --prefer-dist --optimize-autoloader --ignore-platform-reqs --no-dev

cd "${base}/js/forum"
npm install
gulp --production

cd "${base}/js/admin"
npm install
gulp --production
