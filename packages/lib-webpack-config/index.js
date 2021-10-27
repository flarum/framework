const fs = require('fs');
const path = require('path');

const entryPointNames = ['forum', 'admin'];
const entryPointExts = ['js', 'ts'];

function getEntryPoints() {
  const entries = {};

  appLoop: for (const app of entryPointNames) {
    for (const ext of entryPointExts) {
      const file = path.resolve(process.cwd(), `${app}.${ext}`);

      if (fs.existsSync(file)) {
        entries[app] = file;
        continue appLoop;
      }
    }
  }

  if (Object.keys(entries).length === 0) {
    console.error('ERROR: No JS entrypoints could be found.');
  }

  return entries;
}

const useBundleAnalyzer = process.env.ANALYZER === 'true';
const plugins = [];

if (useBundleAnalyzer) {
  plugins.push(new (require('webpack-bundle-analyzer').BundleAnalyzerPlugin)());
}

module.exports = function (options = {}) {
  return {
    // Set up entry points for each of the forum + admin apps, but only
    // if they exist.
    entry: getEntryPoints(),

    plugins,

    resolve: {
      extensions: ['.ts', '.tsx', '.js', '.jsx', '.json'],
    },

    module: {
      rules: [
        {
          // Matches .js, .jsx, .ts, .tsx
          // See: https://regexr.com/5snjd
          test: /\.(j|t)sx?$/,
          loader: 'babel-loader',
          options: {
            presets: [
              '@babel/preset-react',
              '@babel/preset-typescript',
              [
                '@babel/preset-env',
                {
                  modules: false,
                  loose: true,
                },
              ],
            ],
            plugins: [
              ['@babel/plugin-transform-runtime', { useESModules: true }],
              ['@babel/plugin-proposal-class-properties', { loose: true }],
              ['@babel/plugin-proposal-private-methods', { loose: true }],
              [
                '@babel/plugin-transform-react-jsx',
                {
                  pragma: 'm',
                  pragmaFrag: "'['",
                  useBuiltIns: true,
                },
              ],
            ],
          },
        },
      ],
    },

    output: {
      path: path.resolve(process.cwd(), 'dist'),
      library: 'module.exports',
      libraryTarget: 'assign',
      devtoolNamespace: require(path.resolve(process.cwd(), 'package.json')).name,
    },

    externals: [
      {
        '@flarum/core/forum': 'flarum.core',
        '@flarum/core/admin': 'flarum.core',
        jquery: 'jQuery',
      },

      (function () {
        const externals = {};

        if (options.useExtensions) {
          for (const extension of options.useExtensions) {
            externals['@' + extension] =
              externals['@' + extension + '/forum'] =
              externals['@' + extension + '/admin'] =
                "flarum.extensions['" + extension + "']";
          }
        }

        return externals;
      })(),

      // Support importing old-style core modules.
      function ({request}, callback) {
        let matches;
        if ((matches = /^flarum\/(.+)$/.exec(request))) {
          return callback(null, "root flarum.core.compat['" + matches[1] + "']");
        }
        callback();
      },
    ],

    devtool: 'source-map',
  };
};
