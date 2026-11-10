/**
 * @jest-environment node
 */
import { execFileSync } from 'child_process';
import fs from 'fs';
import os from 'os';
import path from 'path';

let extensionDir;

/**
 * Lays out an extension whose two lazily imported components share a module
 * larger than the production splitChunks threshold (20 KB), as in #5405.
 */
beforeEach(() => {
  extensionDir = fs.mkdtempSync(path.join(os.tmpdir(), 'flarum-webpack-config-'));

  const files = {
    'composer.json': JSON.stringify({ name: 'flarum/test-extension' }),
    'js/package.json': JSON.stringify({ name: '@flarum/test-extension', private: true }),
    'js/forum.js': "export * from './src/forum';\n",
    'js/src/forum/index.js':
      "export function boot() {\n  import('./components/ModalA').then((m) => m.default());\n  import('./components/ModalB').then((m) => m.default());\n}\n",
    'js/src/forum/components/ModalA.js': "import lookup from '../shared/big';\n\nexport default () => lookup('a');\n",
    'js/src/forum/components/ModalB.js': "import lookup from '../shared/big';\n\nexport default () => lookup('b');\n",
    'js/src/forum/shared/big.js': `const table = {\n${Array.from({ length: 1000 }, (_, i) => `  k${i}: 'value number ${i} with some padding text',\n`).join('')}};\n\nexport default (key) => table[key];\n`,
  };

  for (const [file, contents] of Object.entries(files)) {
    fs.mkdirSync(path.dirname(path.join(extensionDir, file)), { recursive: true });
    fs.writeFileSync(path.join(extensionDir, file), contents);
  }
});

afterEach(() => {
  fs.rmSync(extensionDir, { recursive: true, force: true });
});

/**
 * Builds the extension with the real config in production mode, from its js/
 * folder as an extension does. It runs in a separate node process because
 * babel-loader's dynamic import fails inside Jest's module sandbox.
 */
function build() {
  const script = `
    const webpack = require(${JSON.stringify(require.resolve('webpack'))});
    const config = require(${JSON.stringify(path.resolve(__dirname, '../src/index.cjs'))})();

    webpack({ ...config, mode: 'production' }, (err, stats) => {
      if (err || stats.hasErrors()) {
        console.error(err || stats.toString('errors-only'));
        process.exit(1);
      }
    });
  `;

  execFileSync(process.execPath, ['-e', script], { cwd: path.join(extensionDir, 'js'), stdio: 'pipe' });

  return path.join(extensionDir, 'js/dist');
}

test('keeps a module shared by lazy chunks inside each of them', () => {
  const dist = build();
  const rootScripts = fs.readdirSync(dist).filter((file) => file.endsWith('.js'));

  expect(rootScripts).toEqual(['forum.js']);
  expect(fs.readFileSync(path.join(dist, 'forum/components/ModalA.js'), 'utf8')).toContain('value number 999');
  expect(fs.readFileSync(path.join(dist, 'forum/components/ModalB.js'), 'utf8')).toContain('value number 999');
});
