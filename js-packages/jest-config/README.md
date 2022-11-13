# Jest config for Flarum

This package generates a [Jest](https://jestjs.io/) config object to run unit & integration tests on Flarum extensions.

## Usage

* Install the package: `yarn add --dev flarum-jest-config`
* Add `"type": "module"` to your `package.json`
* Add `"test": "yarn node --experimental-vm-modules $(yarn bin jest)"` to your `package.json` scripts
* Rename `webpack.config.js` to `webpack.config.cjs`
* Create a `jest.config.cjs` file with the following content:
```js
module.exports = require('flarum-jest-config')();
```
* If you are using TypeScript, add the following to your `tsconfig.json`:
```json
{
  "files": ["node_modules/flarum-jest-config/shims.d.ts"]
}
```
