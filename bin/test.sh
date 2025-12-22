#!/bin/bash

MONOREPO_TEST="framework/core extensions/akismet extensions/approval extensions/flags extensions/likes extensions/mentions extensions/nicknames extensions/statistics extensions/sticky extensions/subscriptions extensions/suspend extensions/tags extensions/messages php-packages/testing/tests"

for test in $MONOREPO_TEST; do
  echo ""
  echo "===> Testing $test"
  echo ""

  # composer test:setup --working-dir=$test
  composer test --working-dir=$test

  echo ""
  echo "===> Done testing $test"
  echo ""
done
