// Use shared globals from flarum-webpack-config
// TEMPORARY: This will likely move to the flarum-webpack-config package.
export * from './webpack-config-shims';

import Application from './src/common/Application';

/**
 * Annotate the types of all global variables specific to flarum/core.
 *
 * IDEs can use this to typehint the globals.
 */
declare global {
  const app: Application;
}
