const fs = require('fs');
const path = require('path');
const { NormalModuleReplacementPlugin } = require('webpack');

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

/**
 * Yarn Plug'n'Play means that dependency hoisting doesn't work like it normally
 * would with the standard `node_modules` configuration. This is by design, as
 * hoisting is unpredictable.
 *
 * This plugin works around this by ensuring references to `@babel/runtime` (which
 * is required at build-time from an extension/core's scope) are redirected to the
 * copy of `@babel/runtime` which is a dependency of this package.
 *
 * This removes the need for hoisting, and allows for Plug'n'Play compatibility.
 *
 * Thanks goes to Yarn's lead maintainer @arcanis for helping me get to this
 * solution.
 */
plugins.push(
  new NormalModuleReplacementPlugin(/^@babel\/runtime(.*)/, (resource) => {
    const path = resource.request.split('@babel/runtime')[1];

    resource.request = require.resolve(`@babel/runtime${path}`);
  })
);

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
          include: /src/, // Only apply this loader to files in the src directory
          loader: path.resolve(__dirname, './autoExportLoader.cjs'),
        },
        {
          // Matches .js, .jsx, .ts, .tsx
          test: /\.[jt]sx?$/,
          loader: require.resolve('babel-loader'),
          options: require('../babel.config.cjs'),
          resolve: {
            fullySpecified: false,
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
        // @todo: remove when dropping compat api
        '@flarum/core/forum': 'flarum.core',
        '@flarum/core/admin': 'flarum.core',
        jquery: 'jQuery',
      },

      function ({ request }, callback) {
        let namespace;
        let id;
        let matches;
        if ((matches = /^flarum\/(.+)$/.exec(request))) {
          namespace = 'core';
          id = matches[1];
        } else if ((matches = /^ext:([^\/]+)\/(?:flarum-(?:ext-)?)?([^\/]+)(?:\/(.+))?$/.exec(request))) {
          namespace = `${matches[1]}-${matches[2]}`;
          id = matches[3];
        } else {
          return callback();
        }

        return callback(null, `root flarum.reg.get('${namespace}', '${id}')`);
      },
    ],

    devtool: 'source-map',
  };
};
