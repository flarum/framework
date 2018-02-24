**Webpack config for Flarum JavaScript compilation.**

This package generates a [Webpack](https://webpack.js.org) config object that will compile JavaScript for use in Flarum. Example usage:

```js
// webpack.config.js
var config = require('flarum-webpack-config');

module.exports = config();
```
