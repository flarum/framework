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
                ['@babel/plugin-transform-react-jsx', {pragma: 'm'}]
              ]
            }
          }
        }
      ]
    },

    output: {
      path: path.resolve(process.cwd(), 'js')
    },

    // For backwards compatibility, search for non-relative-path modules
    // in the source directories. Also make sure the root node_modules
    // directory is searched.
    resolve: {
      modules: [
        path.resolve(process.cwd(), 'js/forum'),
        path.resolve(process.cwd(), 'js/admin'),
        path.resolve(process.cwd(), 'js/common'),
        path.resolve(process.cwd(), 'node_modules'),
        'node_modules'
      ]
    },

    externals: [
      {
        '@flarum/core/forum': 'flarum',
        '@flarum/core/admin': 'flarum',
        'jquery': 'jQuery'
      },

      // Support importing old-style core modules.
      function(context, request, callback) {
        let matches;
        if ((matches = /^flarum\/(.+)$/.exec(request))) {
          return callback(null, 'root flarum.compat[\'' + matches[1] + '\']');
        }
        callback();
      }
    ],

    devtool: 'source-map'
  };
};
