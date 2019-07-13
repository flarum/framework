const config = require('flarum-webpack-config');
const merge = require('webpack-merge');

module.exports = merge(config(), {
  output: {
    library: 'flarum.core'
  },

  // use zepto instead of jquery
  module: {
    rules: [
      {
        test: require.resolve("zepto"),
        use: "imports-loader?this=>window"
      }
    ]
  },
  resolve: {
    alias: {
      jquery: "zepto"
    }
  }
});
