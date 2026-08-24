const path = require('path');
const fs = require('fs');

/**
 * Locate Flarum core's frontend source (its `js` directory).
 *
 * Inside the monorepo, `@flarum/core` resolves as a workspace package. A
 * standalone, Composer-installed extension has no such package; instead core's
 * source is vendored at `vendor/flarum/core/js`. Detect the monorepo case
 * first (so core and the bundled extensions are unaffected) and fall back to
 * the vendored path, so the same config works in both layouts with no setup.
 */
function resolveCoreDir(cwd) {
  // A standalone, Composer-installed extension carries core's frontend source
  // at `vendor/flarum/core/js`. Prefer it when present: the extension under
  // test is the authority on where its own core lives, and this is checked
  // relative to the extension (cwd), not to where this package happens to sit.
  //
  // `cwd` is the directory jest runs from — the extension's `js` dir for a
  // typical layout — so look for the vendor path from there and from its
  // parent (extensions keep `vendor/` beside `js/`, not inside it).
  for (const base of [cwd, path.resolve(cwd, '..')]) {
    const vendored = path.resolve(base, 'vendor/flarum/core/js');

    if (fs.existsSync(vendored)) {
      return vendored;
    }
  }

  try {
    // Monorepo (or any install where @flarum/core resolves as a package).
    return path.dirname(require.resolve('@flarum/core/package.json', { paths: [cwd, __dirname] }));
  } catch (e) {
    throw new Error(
      '@flarum/jest-config could not locate Flarum core. Expected a Composer-vendored copy at ' +
        '`vendor/flarum/core/js`, or the `@flarum/core` package (inside the monorepo). Run ' +
        '`composer install` so `flarum/core` is present, then try again.'
    );
  }
}

module.exports = (options = {}) => {
  const cwd = process.cwd();
  const coreDir = resolveCoreDir(cwd);

  return {
    testEnvironment: 'jsdom',
    extensionsToTreatAsEsm: ['.ts', '.tsx'],
    transform: {
      '^.+\\.[tj]sx?$': ['babel-jest', require('flarum-webpack-config/babel.config.cjs')],
      '^.+\\.tsx?$': [
        'ts-jest',
        {
          useESM: true,
        },
      ],
    },
    preset: 'ts-jest',
    setupFiles: [path.resolve(__dirname, 'pollyfills.js')],
    setupFilesAfterEnv: [path.resolve(__dirname, 'setup-env.js')],
    moduleDirectories: ['node_modules', 'src'],
    // Transform the extension's own files and, when core is vendored outside
    // it, core's source too — otherwise its TypeScript is loaded untransformed
    // and fails on the first type annotation.
    roots: ['<rootDir>', coreDir],
    // Core's source imports its runtime deps by bare name (`mithril`, …). When
    // core is vendored, those files resolve modules from core's own ancestry,
    // not the extension's node_modules where a single `yarn install` puts them,
    // so add the extension's node_modules as an explicit resolver search path.
    modulePaths: [path.join(cwd, 'node_modules')],
    // Resolve `@flarum/core/...` imports (used by setup-env and the bootstrap
    // helpers, and available for tests) to wherever core actually lives.
    moduleNameMapper: {
      '^@flarum/core/(.*)$': path.join(coreDir, '$1'),
    },
    // Exposed so the bootstrap helpers can read core's bundled locale file.
    globals: {
      __FLARUM_CORE_DIR__: coreDir,
    },
    ...options,
  };
};
