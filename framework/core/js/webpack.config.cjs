const config = require('flarum-webpack-config');
const { merge } = require('webpack-merge');

module.exports = merge(config(), {
  output: {
    library: 'flarum.core',
  },
});
