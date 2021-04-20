const fs = require('fs');
const path = require('path');
const webpack = require('webpack');
const TerserPlugin = require("terser-webpack-plugin");

module.exports = function(options = {}) {
  return {
    // Set up entry points for each of the forum + admin apps, but only
    // if they exist.
    entry: function() {
      const entries = {};

      for (const app of ['forum', 'admin']) {
        const file = path.resolve(process.cwd(), app+'.js');
        if (fs.existsSync(file)) {
          const entry = `/${app}/${require(path.resolve(process.cwd(), 'package.json')).name.replace('@', '').replace('/', '-')}`
          entries[entry] = file;
        }
      }

      return entries;
    }(),

    resolve: {
      extensions: ['.ts', '.tsx', '.js', '.json'],
    },


  optimization: {
      runtimeChunk: {
        name: entrypoint => `${entrypoint.name}~runtime`
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
              return `${entry[0].name.match(/[^\/]*\/[^\/]*/)[0]}/vendor`;
            }
          },
          common: {
            test: /[\\/]common[\\/]/,
            priority: -20,
            reuseExistingChunk: true,
            name: '/common/common'
          },
        },
      },
      /**minimizer: [new TerserPlugin({
        test: /\.(tsx?|js)$/,
        parallel: true,
        terserOptions: {
          ecma: 8,
          warnings: false,
          parse: {
            ecma: 8,
          },
          compress: {
            warnings: false,
            comparisons: false,
          },
          mangle: {
            safari10: true,
          },
          module: false,
          output: {
            ecma: 5,
            comments: false,
            ascii_only: true,
          },
          toplevel: false,
          nameCache: null,
          ie8: false,
          keep_classnames: undefined,
          keep_fnames: false,
          safari10: false,
        },
      })],**/
    },

    module: {
      rules: [
        {
          test: /\.(tsx?|js)$/,
          exclude: /node_modules/,
          use: {
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
                ['@babel/plugin-syntax-dynamic-import']
              ]
            }
          }
        }
      ]
    },

    output: {
      path: path.resolve(process.cwd(), 'dist'),
      library: 'flarum.core',
      libraryTarget: 'assign',
      chunkFilename: '[name].js',
      publicPath: "/assets/",
      filename: '[name].js',
      devtoolNamespace: require(path.resolve(process.cwd(), 'package.json')).name
    },

    externals: [
      {
        '@flarum/core/forum': 'flarum.core',
        '@flarum/core/admin': 'flarum.core',
        'jquery': 'jQuery'
      },

      function() {
        const externals = {};

        if (options.useExtensions) {
          for (const extension of options.useExtensions) {
            externals['@'+extension] =
              externals['@'+extension+'/forum'] =
                externals['@'+extension+'/admin'] = "flarum.extensions['"+extension+"']";
          }
        }

        return externals;
      }(),

      // Support importing old-style core modules.
      function(context, request, callback) {
        let matches;
        if ((matches = /^flarum\/(.+)$/.exec(request))) {
          return callback(null, 'root flarum.core.compat[\'' + matches[1] + '\']');
        }
        callback();
      }
    ],

    devtool: 'source-map'
  };
};
