/**
 * @jest-environment node
 *
 * Regression tests for the "checkout path contains a `src` segment" bug.
 *
 * The config detects an extension's own source files by anchoring to the
 * project's `src` directory. Earlier versions matched the substring "src"
 * anywhere in a module's absolute path, which broke when the project itself was
 * checked out under a path containing a `src` segment (e.g. `~/src/my-ext`):
 *
 *  - the auto-export loader ran on `node_modules` (e.g. `@babel/runtime`
 *    helpers) and emitted invalid `flarum.reg.add(...)`, failing the build;
 *  - `autoChunkNameLoader` produced chunk names that leaked the checkout path.
 *
 * These tests pin the anchored behaviour so it can't regress to loose matching.
 */
import path from 'path';
import makeConfig from '../src/index.cjs';
import autoChunkNameLoader from '../src/autoChunkNameLoader.cjs';

describe('index.cjs: source loaders are anchored to the project `src` directory', () => {
  const srcDir = path.resolve(process.cwd(), 'src') + path.sep;

  // Avoid noise from getEntryPoints() when no forum.ts/admin.ts entry exists.
  const config = (() => {
    const spy = jest.spyOn(console, 'error').mockImplementation(() => {});
    try {
      return makeConfig();
    } finally {
      spy.mockRestore();
    }
  })();

  const sourceLoaderRules = config.module.rules.filter(
    (rule) => typeof rule.loader === 'string' && /(autoExportLoader|autoChunkNameLoader)\.cjs$/.test(rule.loader)
  );

  test('both source loaders use an absolute `src` path include, not a loose /src/ regex', () => {
    expect(sourceLoaderRules).toHaveLength(2);

    for (const rule of sourceLoaderRules) {
      expect(rule.include instanceof RegExp).toBe(false);
      expect(rule.include).toBe(srcDir);
    }
  });

  test('the include matches extension source but not node_modules under an unrelated "src" path', () => {
    const include = sourceLoaderRules[0].include;

    // Mirror webpack's own condition matching: a RegExp uses `.test()`, a
    // string matches by directory prefix.
    const matches = (resource) => (include instanceof RegExp ? include.test(resource) : resource.startsWith(include));

    // The extension's own source is included.
    expect(matches(path.join(process.cwd(), 'src', 'forum', 'index.ts'))).toBe(true);

    // A node_modules file whose absolute path contains an unrelated `src`
    // segment must NOT be treated as extension source. A loose /src/ regex
    // wrongly matches this — that was the bug.
    expect(matches(path.join(path.sep + 'home', 'src', 'proj', 'node_modules', '@babel', 'runtime', 'defineProperty.js'))).toBe(false);
  });
});

describe('autoChunkNameLoader: chunk names are relative to the extension `src`', () => {
  // Simulate a project checked out under a path that itself contains a `src`
  // segment. The extension's own source lives at `<root>/src`.
  const root = path.resolve(path.sep + 'home', 'src', 'acme', 'js');
  const resourcePath = path.join(root, 'src', 'forum', 'index.ts');

  const runLoader = (source) =>
    autoChunkNameLoader.call(
      {
        query: { composerPath: path.resolve(__dirname, 'fixtures', 'composer.json') },
        rootContext: root,
        resourcePath,
        addDependency() {},
      },
      source
    );

  test('internal dynamic import gets a chunk name relative to <root>/src', () => {
    const output = runLoader("export function load() {\n  return import('./Lazy');\n}\n");

    // Relative to `<root>/src` -> `forum/Lazy`, NOT polluted by the leading
    // `/home/src/...` segment of the checkout path (old output was `acme/js/`).
    expect(output).toContain("webpackChunkName: 'forum/Lazy'");
    expect(output).not.toContain('acme/js');
  });

  test('external (flarum/ and ext:) imports are converted to async module imports', () => {
    const output = runLoader("export function load() {\n  return import('ext:flarum/tags/forum/components/TagsPage');\n}\n");

    expect(output).toContain("flarum.reg.asyncModuleImport('ext:flarum/tags/forum/components/TagsPage')");
  });
});
