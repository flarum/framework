/*
 * The frontend test factories now live in `@flarum/jest-config` so that
 * standalone, Composer-installed extensions can use them too (core's `tests/`
 * directory is stripped from the Composer package). Re-exported here so
 * existing imports of `tests/factory` inside the monorepo keep working.
 */

export { makeUser, makeDiscussion } from '@flarum/jest-config/factory';
