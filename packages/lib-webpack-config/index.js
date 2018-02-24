const path = require('path');
const webpack = require('webpack');

module.exports = function(options = {}) {
  const config = {
    module: {
      rules: [
        {
          test: /\.js$/,
          exclude: /node_modules/,
          use: {
            loader: 'babel-loader',
            options: {
              presets: ['@babel/preset-env', '@babel/preset-react'],
              plugins: [
                ['@babel/plugin-transform-runtime'],
                ['@babel/plugin-proposal-class-properties'],
                ['@babel/plugin-transform-react-jsx', {pragma: 'm'}]
              ]
            }
          }
        }
      ]
    },

    // For backwards compatibility, search for non-relative-path modules
    // in the `src` and `lib` directories. Also make sure the root node_modules
    // directory is searched, otherwise importing a module from a file
    // inside `lib` won't work.
    resolve: {
      modules: [
        path.resolve(process.cwd(), 'src'),
        path.resolve(process.cwd(), '../lib'),
        path.resolve(process.cwd(), 'node_modules'),
        'node_modules'
      ]
    },

    // Support importing old-style core modules.
    externals: [
      function(context, request, callback) {
        let matches;
        if ((matches = /^flarum\/(.+)$/.exec(request))) {
          return callback(null, 'root flarum.compat[\'' + matches[1] + '\']');
        }
        callback();
      }
    ]
  };

  return config;
};
