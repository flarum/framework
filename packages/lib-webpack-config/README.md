# Webpack config for Flarum JS/TS compilation

This package generates a [Webpack](https://webpack.js.org) config object that will compile JavaScript for use in Flarum.

## Usage

**webpack.config.js**

```js
var config = require('flarum-webpack-config');

module.exports = config(options);
```

To merge in custom Webpack config options, use [webpack-merge](https://www.npmjs.com/package/webpack-merge).

### Webpack Bundle Analyzer

You can view a visual representation of your JS Bundle by building with Webpack Bundle Analyzer.

Add another build script to your `package.json` like the one below:

```json
{
  "analyze": "npx cross-env ANALYZER=true npm run build"
}
```

## Typescript

You'll need to configure a `tsconfig.json` file to ensure your IDE sets up Typescript support correctly.

For details about this, see the [`flarum/flarum-tsconfig` repository](https://github.com/flarum/flarum-tsconfig)

## Options

### `useExtensions`

`Array<string>`, defaults to `[]`.

An array of extensions whose modules should be made available. This is a shortcut to add [`externals`](https://webpack.js.org/configuration/externals/) configuration for extension modules. Imported extension modules will not be bundled, but will instead refer to the extension's exports included in the Flarum runtime (ie. `flarum.extensions["vendor/package"]`).

For example, to access the Tags extension module within your extension:

**forum.js**

```js
import { Tag } from '@flarum/tags/forum';
```

**webpack.config.js**

```js
module.exports = config({
  useExtensions: ['flarum/tags'],
});
```
