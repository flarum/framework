const fs = require('fs');
const path = require('path');
const webpack = require('webpack');

module.exports = function(options = {}) {
  return {
    // Set up entry points for each of the forum + admin apps, but only
    // if they exist.
    entry: function() {
      const entries = {};

      for (const app of ['forum', 'admin']) {
        const file = path.resolve(process.cwd(), app+'.js');
        if (fs.existsSync(file)) {
          entries[app] = file;
        }
      }

      return entries;
    }(),

    module: {
      rules: [
        {
          test: /\.js$/,
          exclude: /node_modules/,
          use: {
            loader: 'babel-loader',
            options: {
              presets: [
                ['@babel/preset-env', {modules: false, loose: true}],
                ['@babel/preset-react']
              ],
              plugins: [
                ['@babel/plugin-transform-runtime', {useESModules: true}],
                ['@babel/plugin-proposal-class-properties'],
                ['@babel/plugin-transform-react-jsx', {pragma: 'm'}],
                ['@babel/plugin-transform-object-assign']
              ]
            }
          }
        }
      ]
    },

    output: {
      path: path.resolve(process.cwd(), 'dist'),
      library: 'module.exports',
      libraryTarget: 'assign',
      devtoolNamespace: require(path.resolve(process.cwd(), 'package.json')).name
    },

    externals: [
      {
        '@flarum/core/forum': 'flarum.core',
        '@flarum/core/admin': 'flarum.core',
        'jquery': 'jQuery',
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
