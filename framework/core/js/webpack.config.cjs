const config = require('flarum-webpack-config');
const { merge } = require('webpack-merge');

module.exports = merge(config(), {
  output: {
    // @todo decide what to do with this
    library: 'flarum.core',
  },
});
