# Jest config for Flarum

This package provides a [Jest](https://jestjs.io/) config object to run unit & integration tests on Flarum extensions.

## Usage

- Install the package: `yarn add --dev @flarum/jest-config`
- Add `"type": "module"` to your `package.json`
- Add `"test": "yarn node --experimental-vm-modules $(yarn bin jest)"` to your `package.json` scripts
- Rename `webpack.config.js` to `webpack.config.cjs`
- Create a `jest.config.cjs` file with the following content:

```js
module.exports = require('@flarum/jest-config')();
```

- If you are using TypeScript, create `tsconfig.test.json` with the following content:

```json
{
  "extends": "./tsconfig.json",
  "include": ["tests/**/*"],
  "files": ["../../../node_modules/@flarum/jest-config/shims.d.ts"]
}
```

## Troubleshooting

### `symbol.charCodeAt is not a function` when a test imports Flarum's source

If Babel fails on a regular expression such as `/[^\p{L}\p{N}]/u` with `TypeError: symbol.charCodeAt is not a function`, your `yarn.lock` holds two copies of `regenerate`. This can happen after upgrading a single package, which keeps the old `regenerate@^1.4.2` entry next to the `regenerate@^1.5.0` that newer Babel releases need.

Delete the `regenerate@^1.4.2` block from `yarn.lock` and run `yarn install`. Both ranges then resolve to one copy.
