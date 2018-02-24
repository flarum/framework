**Webpack config for Flarum JavaScript compilation.**

This package generates a [Webpack](https://webpack.js.org) config object that will compile JavaScript for use in Flarum. Example usage:

```js
// webpack.config.js
var config = require('flarum-webpack-config');

module.exports = config(options);
```

## Options

* `compatPrefix` Old-style module prefix (eg. `flarum/sticky`) to alias. Setting this will also enable importing old-style core modules (eg. `import foo from 'flarum/foo'`).
