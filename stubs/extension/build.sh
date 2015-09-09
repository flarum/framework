#!/usr/bin/env bash

base=${PWD}

if [ ! -f flarum.json ]; then
echo "Could not find flarum.json file!"
exit 1
fi


extension=$(php <<CODE
<?php
\$flarum = json_decode(file_get_contents('flarum.json'), true);
echo array_key_exists('name', \$flarum) ? \$flarum['name'] : '';
CODE
)

release=/tmp/${extension}

rm -rf ${release}
mkdir ${release}

git archive --format zip --worktree-attributes HEAD > ${release}/release.zip

cd ${release}
unzip release.zip -d ./
rm release.zip

# Delete files
rm -rf ${release}/build.sh

# Install all Composer dependencies
composer install --prefer-dist --optimize-autoloader --ignore-platform-reqs --no-dev

cd "${release}/js"
if [ -f bower.json ]; then
bower install
fi

for app in forum admin; do
    cd "${release}/js"

    if [ -d $app ]; then
      cd $app

      if [ -f bower.json ]; then
        bower install
      fi

      npm install
      gulp --production
      rm -rf node_modules bower_components
    fi
done

rm -rf "${release}/extensions/${extension}/js/bower_components"
wait

# Finally, create the release archive
cd ${release}
find . -type d -exec chmod 0750 {} +
find . -type f -exec chmod 0644 {} +
chmod 0775 .
zip -r ${extension}.zip ./
mv ${extension}.zip ${base}/${extension}.zip
