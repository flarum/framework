// TEMPORARY
// This will go in flarum-webpack-config when ready
// In core for development purposes

const fs = require('fs');
const path = require('path');
const webpack = require('webpack');
const FriendlyErrorsPlugin = require('friendly-errors-webpack-plugin');

const plugins = [new FriendlyErrorsPlugin()];

// add production plugins
if (process.env.NODE_ENV === 'production') {
    plugins.push(
        new webpack.DefinePlugin({
            'process.env': {
                NODE_ENV: '"production"',
            },
        }),
        new webpack.optimize.UglifyJsPlugin({
            sourceMap: true,
            compress: {
                warnings: false,
            },
        }),
        new webpack.LoaderOptionsPlugin({
            minimize: true,
        })
    );
}

// if --analyze is found in command arguments,
// run webpack bundle analyzer to analyze dependencies
// and the space they take up
if (process.argv.includes('--analyze')) {
    plugins.push(new (require('webpack-bundle-analyzer').BundleAnalyzerPlugin)());
}

module.exports = (options = {}) => {
    return {
        devtool: 'source-map',

        watchOptions: {
            aggregateTimeout: 300,
            poll: 1000,
        },

        // Set up entry points for each of the forum + admin apps, but only
        // if they exist.
        entry: (function() {
            const entries = {};

            for (const app of ['forum', 'admin']) {
                const file = path.resolve(process.cwd(), app + '.ts');
                if (fs.existsSync(file)) {
                    entries[app] = file;
                }
            }

            return entries;
        })(),

        output: {
            path: path.resolve(process.cwd(), './dist'),
            publicPath: '/dist/',
            library: 'module.exports',
            libraryTarget: 'assign',
            devtoolNamespace: require(path.resolve(process.cwd(), 'package.json')).name,
        },

        module: {
            rules: [
                {
                    test: /\.js$/,
                    enforce: 'pre',
                    loader: 'source-map-loader',
                },
                {
                    test: /\.(js|jsx|tsx|ts)$/,
                    exclude: /node_modules\/(?!babel-runtime)/,
                    loader: 'babel-loader',
                    options: {
                        presets: [
                            [
                                '@babel/preset-env',
                                {
                                    modules: false,
                                    loose: true,
                                    targets: {
                                        browsers: ['> 1%', 'last 2 versions', 'not ie <= 8', 'ie >= 11'],
                                    },
                                },
                            ],
                            ['@babel/preset-typescript'],
                            ['@babel/preset-react'],
                        ],
                        plugins: [
                            ['@babel/plugin-transform-runtime', { useESModules: true }],
                            ['@babel/plugin-proposal-class-properties', { loose: true }],
                            ['@babel/plugin-transform-react-jsx', { pragma: 'm' }],
                            ['@babel/plugin-transform-object-assign'],
                            ['@babel/plugin-syntax-dynamic-import'],
                            ['@babel/plugin-proposal-optional-chaining'],
                        ],
                    },
                },
            ],
        },

        resolve: {
            extensions: ['.ts', '.tsx', '.js', '.json'],
        },

        externals: [
            {
                mithril: 'm',
            },

            (function() {
                const externals = {};

                if (options.useExtensions) {
                    for (const extension of options.useExtensions) {
                        externals['@' + extension] = externals['@' + extension + '/forum'] = externals['@' + extension + '/admin'] =
                            "flarum.extensions['" + extension + "']";
                    }
                }

                return externals;
            })(),

            // Support importing old-style core modules.
            function(context, request, callback) {
                const matches = /^flarum\/(.+?)(?:\/(.+))?$/.exec(request);

                if (matches) {
                    const lib = matches[2] ? `flarum.core.compat['${matches[2]}']` : 'flarum.core.compat';

                    return callback(null, `root ${lib}`);
                }

                callback();
            },
        ],

        plugins,
    };
};
