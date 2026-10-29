const path = require('path');
const fs = require('fs');

/**
 * The shared Flarum babel config, with `@babel/preset-env` forced to emit ES
 * modules. Under Jest that preset otherwise defaults to CommonJS (`modules:
 * 'auto'`), which does not match `extensionsToTreatAsEsm` and breaks module
 * loading. Everything else (the Mithril JSX pragma, TypeScript, the plugins)
 * is kept as-is.
 */
function esmBabelConfig() {
  const config = require('flarum-webpack-config/babel.config.cjs');

  return {
    ...config,
    presets: (config.presets || []).map((preset) => {
      const name = Array.isArray(preset) ? preset[0] : preset;

      if (typeof name === 'string' && name.includes('preset-env')) {
        const options = Array.isArray(preset) ? preset[1] || {} : {};

        return [name, { ...options, modules: false }];
      }

      return preset;
    }),
  };
}

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
    // Everything is transformed by babel-jest, which understands Flarum's
    // Mithril-flavoured JSX (pragma `m`). We force `@babel/preset-env` to emit
    // ES modules (`modules: false`) so the output matches
    // `extensionsToTreatAsEsm` — the shared babel config defaults to `auto`,
    // which is CommonJS under Jest and clashes with ESM loading (it surfaces as
    // "Unexpected token" or a broken `class extends` once core's TypeScript is
    // pulled in from outside the extension).
    transform: {
      '^.+\\.[tj]sx?$': ['babel-jest', esmBabelConfig()],
    },
    setupFiles: [path.resolve(__dirname, 'pollyfills.js')],
    setupFilesAfterEnv: [path.resolve(__dirname, 'setup-env.js')],
    moduleDirectories: ['node_modules', 'src'],
    // This package's setup and bootstrap files live in the extension's
    // node_modules and import core's TypeScript. Jest ignores node_modules for
    // transformation by default, and that ignore extends to the core source
    // pulled in through them — so it reaches Jest untransformed and dies on its
    // first type annotation. Un-ignore this package (and only this one) so its
    // helpers, and the core code they import, are transformed; everything else
    // under node_modules stays ignored, as some ships non-strict JS Jest can't
    // parse.
    transformIgnorePatterns: ['/node_modules/(?!@flarum/jest-config/)', '\\.pnp\\.[^\\/]+$'],
    // Transform the extension's own files and, when core is vendored outside
    // it, core's source too — otherwise its TypeScript is loaded untransformed
    // and fails on the first type annotation.
    roots: ['<rootDir>', coreDir],
    // Core's source imports its runtime deps by bare name (`mithril`, …). When
    // core is vendored, those files resolve modules from core's own ancestry,
    // not the extension's node_modules where a single `yarn install` puts them,
    // so add the extension's node_modules as an explicit resolver search path.
    modulePaths: [path.join(cwd, 'node_modules')],
    // Resolve core imports to wherever core actually lives. `@flarum/core/...`
    // is used by this package's own setup and bootstrap helpers; `flarum/...`
    // is the alias extensions import core through at runtime (webpack turns it
    // into a `flarum.reg` lookup), so map it to core's source for tests too —
    // otherwise an extension's own components, which import via `flarum/...`,
    // cannot be tested.
    moduleNameMapper: {
      '^@flarum/core/(.*)$': path.join(coreDir, '$1'),
      '^flarum/(.*)$': path.join(coreDir, 'src', '$1'),
    },
    // Exposed so the bootstrap helpers can read core's bundled locale file.
    globals: {
      __FLARUM_CORE_DIR__: coreDir,
    },
    ...options,
  };
};
