const fs = require('fs');
const path = require('path');
const webpack = require('webpack');
const TerserPlugin = require("terser-webpack-plugin");
const BundleAnalyzerPlugin = require('webpack-bundle-analyzer').BundleAnalyzerPlugin;

process.traceDeprecation = true;

module.exports = function (options = {}) {
  return {
    // Set up entry points for each of the forum + admin apps, but only
    // if they exist.
    entry: function () {
      const entries = {};

      for (const app of ['forum', 'admin']) {
        const file = path.resolve(process.cwd(), app + '.js');
        if (fs.existsSync(file)) {
          entries[app] = {
            import: file,
            filename: `${app}/${require(path.resolve(process.cwd(), 'package.json')).name.replace('@', '').replace('/', '-')}.js`,
            library: {
              name: `flarum.core`,
              type: 'assign',
            }
          };
        }
      }

      return entries;
    }(),

    resolve: {
      extensions: ['.ts', '.tsx', '.js', '.json'],
    },

    experiments: {
      topLevelAwait: true
    },

    optimization: {
      runtimeChunk: {
        name: entrypoint => `${entrypoint.name}/runtime`
      },
      splitChunks: {
        chunks: 'all',
        minChunks: 2,
        maxInitialRequests: Infinity,
        cacheGroups: {
          vendor: {
            test: /[\\/]node_modules[\\/]/,
            priority: -10,
            name(module, entry) {
              return `${entry[0].name}/vendor`;
            }
          },
          common: {
            test: /[\\/]common[\\/]/,
            priority: -20,
            reuseExistingChunk: true,
            name: 'common/common'
          },
        },
      },
      minimize: true,
      minimizer: [new TerserPlugin({
        parallel: true,
        terserOptions: {
          module: true,
          sourceMap: true,
          safari10: true
        }
      })],
    },

    module: {
      rules: [
        {
          test: /\.(tsx?|js)$/,
          exclude: /node_modules/,
          use: [
            {
              loader: 'babel-loader',
              options: {
                presets: [
                  ['@babel/preset-env', {modules: false, loose: true}],
                  ['@babel/preset-react'],
                  ['@babel/preset-typescript']
                ],
                plugins: [
                  ['@babel/plugin-transform-runtime', {useESModules: true}],
                  ['@babel/plugin-proposal-class-properties'],
                  ['@babel/plugin-transform-react-jsx', {pragma: 'm'}],
                  ['@babel/plugin-transform-object-assign'],
                  ['@babel/plugin-syntax-dynamic-import'],
                  ['@babel/plugin-proposal-export-default-from']
                ]
              }
            },
            {
              loader: path.resolve(__dirname, 'CustomLoader.js')
            }
          ]
        },
      ]
    },

    output: {
      path: path.resolve(process.cwd(), 'dist'),
      chunkFilename: '[name].js',
      publicPath: "/assets/",
      devtoolNamespace: require(path.resolve(process.cwd(), 'package.json')).name
    },

    externals: [
      {
        '@flarum/core/forum': 'flarum.core',
        '@flarum/core/admin': 'flarum.core',
        'jquery': 'jQuery'
      },

      function () {
        const externals = {};

        if (options.useExtensions) {
          for (const extension of options.useExtensions) {
            externals['@' + extension] =
              externals['@' + extension + '/forum'] =
                externals['@' + extension + '/admin'] = "flarum.extensions['" + extension + "']";
          }
        }

        return externals;
      }(),

      // Support importing old-style core modules.
      function ({context, request}, cb) {
        let matches;
        if ((matches = /^flarum\/(.+)$/.exec(request))) {
          return cb(null, `window.flreg.get('${matches[1]}')`);
        }
        cb();
      }
    ],

    devtool: 'source-map'
  };
};
