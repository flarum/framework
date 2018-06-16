**Webpack config factory for Flarum JavaScript compilation.**

This package generates a [Webpack](https://webpack.js.org) config object that will compile JavaScript for use in Flarum.

## Usage

**webpack.config.js**

```js
var config = require('flarum-webpack-config');

module.exports = config();
```

To merge in custom Webpack config options, use [webpack-merge](https://www.npmjs.com/package/webpack-merge).
