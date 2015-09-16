#!/usr/bin/env bash
cd $(dirname $0)
base="${PWD}/.."

cd $base

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

bash "${base}/scripts/compile.sh"
wait

# Delete files
rm -rf ${release}/scripts
rm -rf `find . -type d -name node_modules`
rm -rf `find . -type d -name bower_components`

# Finally, create the release archive
cd ${release}
find . -type d -exec chmod 0750 {} +
find . -type f -exec chmod 0644 {} +
chmod 0775 .
zip -r ${extension}.zip ./
mv ${extension}.zip ${base}/${extension}.zip
