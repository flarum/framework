/**
 * @jest-environment node
 */
import { execFileSync } from 'child_process';
import fs from 'fs';
import os from 'os';
import path from 'path';

let tmpDir;
let extensionDir;

/**
 * Lays out an extension checked out under a directory named `src`, so every
 * path in the build, its node_modules included, contains a `src` segment.
 */
beforeEach(() => {
  tmpDir = fs.mkdtempSync(path.join(os.tmpdir(), 'flarum-webpack-config-'));
  extensionDir = path.join(tmpDir, 'src', 'acme-ext');

  const files = {
    'composer.json': JSON.stringify({ name: 'acme/flarum-ext' }),
    'js/package.json': JSON.stringify({ name: '@acme/flarum-ext', private: true }),
    'js/forum.js': "export * from './src/forum';\n",
    'js/src/forum/index.js':
      "import helper from 'helper';\n\nexport function boot() {\n  helper();\n  import('./components/ModalA');\n  import('./components/ModalB');\n}\n",
    'js/src/forum/components/ModalA.js': "export default () => 'a';\n",
    'js/src/forum/components/ModalB.js': "export default () => 'b';\n",
    'js/node_modules/helper/package.json': JSON.stringify({ name: 'helper', main: 'index.js' }),
    'js/node_modules/helper/index.js': 'function helper() {\n  return 1;\n}\n\nexport { helper as default };\n',
  };

  for (const [file, contents] of Object.entries(files)) {
    fs.mkdirSync(path.dirname(path.join(extensionDir, file)), { recursive: true });
    fs.writeFileSync(path.join(extensionDir, file), contents);
  }
});

afterEach(() => {
  fs.rmSync(tmpDir, { recursive: true, force: true });
});

/**
 * Builds the extension with the real config in production mode, from its js/
 * folder. It runs in a separate node process because babel-loader's dynamic
 * import fails inside Jest's module sandbox.
 */
function build() {
  const script = `
    const webpack = require(${JSON.stringify(require.resolve('webpack'))});
    const config = require(${JSON.stringify(path.resolve(__dirname, '../src/index.cjs'))})();

    webpack({ ...config, mode: 'production', optimization: { ...config.optimization, minimize: false } }, (err, stats) => {
      if (err || stats.hasErrors()) {
        console.error(err || stats.toString('errors-only'));
        process.exit(1);
      }
    });
  `;

  execFileSync(process.execPath, ['-e', script], { cwd: path.join(extensionDir, 'js'), stdio: 'pipe' });

  return path.join(extensionDir, 'js/dist');
}

test('builds an extension whose dependencies are under a path containing src', () => {
  const forum = fs.readFileSync(path.join(build(), 'forum.js'), 'utf8');

  expect(forum).not.toMatch(/flarum\.reg\.add\('acme-ext', '[^']*helper/);
});

test('names lazy chunks after their path inside the extension src', () => {
  const dist = build();
  const forum = fs.readFileSync(path.join(dist, 'forum.js'), 'utf8');

  expect(fs.existsSync(path.join(dist, 'forum/components/ModalA.js'))).toBe(true);
  expect(fs.existsSync(path.join(dist, 'forum/components/ModalB.js'))).toBe(true);
  expect(forum).toContain("'acme-ext', 'forum/components/ModalA'");
  expect(forum).toContain("'acme-ext', 'forum/components/ModalB'");
});
