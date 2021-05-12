const config = require('flarum-webpack-config');

const { BundleAnalyzerPlugin } = require('webpack-bundle-analyzer');
const merge = require('webpack-merge');

const useBundleAnalyzer = process.env.ANALYZER === 'true';

const plugins = [];

if (useBundleAnalyzer) {
  plugins.push(new BundleAnalyzerPlugin());
}

module.exports = merge(config(), {
  output: {
    library: 'flarum.core',
  },

  plugins,
});
