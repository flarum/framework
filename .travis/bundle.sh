#!/bin/bash

set -e # Exit if anything fails

cd js
npm i -g npm@6.1.0
npm ci
npm run build

git add dist/* -f
git commit -m "Bundled output for commit $TRAVIS_COMMIT [skip ci]"

eval `ssh-agent -s`
openssl aes-256-cbc -K $ENCRYPTION_KEY -iv $ENCRYPTION_IV -in ../.deploy.enc | ssh-add -

git config user.name "flarum-bot"
git config user.email "bot@flarum.org"

git push git@github.com:$TRAVIS_REPO_SLUG.git $TRAVIS_BRANCH
