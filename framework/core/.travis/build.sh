#!/bin/bash

main() {
  while getopts ":k:i:" opt; do
    case $opt in
      k) encrypted_key="$OPTARG"
      ;;
      i) encrypted_iv="$OPTARG"
      ;;
      \?) echo "Invalid option -$OPTARG" >&2
      ;;
    esac
  done

  git checkout -f $TRAVIS_BRANCH
  git config user.name "flarum-bot"
  git config user.email "bot@flarum.org"

  cd js
  npm i -g npm@6.1.0
  npm ci
  npm run build

  git add dist/* -f
  git commit -m "Bundled output for commit $TRAVIS_COMMIT [skip ci]"

  eval `ssh-agent -s`
  openssl aes-256-cbc -K $encrypted_key -iv $encrypted_iv -in ../.deploy.enc -d | ssh-add -

  git push git@github.com:$TRAVIS_REPO_SLUG.git $TRAVIS_BRANCH
}

main "$@"
