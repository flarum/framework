const config = require('flarum-webpack-config');
const webpack = require('webpack');
const merge = require('webpack-merge');

module.exports = merge(config({ compat: true }), {
  output: {
    library: 'flarum'
  },
  plugins: [
    new webpack.IgnorePlugin(/^\.\/locale$/, /moment$/)
  ]
});
