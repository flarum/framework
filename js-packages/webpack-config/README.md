**Webpack config for Flarum JavaScript compilation.**

This package generates a [Webpack](https://webpack.js.org) config object that will compile JavaScript for use in Flarum.

## Usage

**webpack.config.js**

```js
var config = require('flarum-webpack-config');

module.exports = config(options);
```

To merge in custom Webpack config options, use [webpack-merge](https://www.npmjs.com/package/webpack-merge).

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
  useExtensions: ['flarum/tags']
});
```
