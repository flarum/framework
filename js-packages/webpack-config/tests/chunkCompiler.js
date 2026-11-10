import path from 'path';
import webpack from 'webpack';
import { createFsFromVolume, Volume } from 'memfs';
import RegisterAsyncChunksPlugin from '../src/RegisterAsyncChunksPlugin.cjs';

/**
 * Compiles a fixture with the pieces that produce chunk registrations: the
 * loader that names each async import after the module it pulls in, and the
 * plugin that turns those names into `flarum.reg.addChunkModule` calls.
 *
 * Production mode, as extensions build: the plugin reads module sources after
 * code generation, which development mode's eval devtool has already baked
 * into strings. Chunk ids are named so a test can tell which chunk a
 * registration points at. Output goes to memfs; every emitted asset is
 * returned.
 */
export default (fixture, options = {}) => {
  const compiler = webpack({
    context: __dirname,
    mode: 'production',
    // As flarum-webpack-config builds; it also changes how module sources are
    // held, which is what the plugin appends to.
    devtool: 'source-map',
    entry: { forum: `./${fixture}` },
    output: {
      path: path.resolve(__dirname),
      filename: '[name].js',
      chunkFilename: '[name].js',
    },
    resolve: { extensions: ['.js'] },
    plugins: [new RegisterAsyncChunksPlugin({ composerPath: path.resolve(__dirname, 'composer.json') })],
    module: {
      rules: [
        {
          test: /\.js$/,
          // This package is `"type": "module"`, which would otherwise demand an
          // extension on every relative import; extensions write theirs without.
          resolve: { fullySpecified: false },
          use: {
            loader: path.resolve(__dirname, '../src/autoChunkNameLoader.cjs'),
            options: { ...options, composerPath: path.resolve(__dirname, 'composer.json') },
          },
        },
      ],
    },
    optimization: { minimize: false, minimizer: [], chunkIds: 'named', moduleIds: 'named' },
  });

  compiler.outputFileSystem = createFsFromVolume(new Volume());
  compiler.outputFileSystem.join = path.join.bind(path);

  return new Promise((resolve, reject) => {
    compiler.run((err, stats) => {
      if (err) return reject(err);
      if (stats.hasErrors()) return reject(stats.toJson().errors);

      const outDir = compiler.options.output.path;
      const assets = {};

      for (const name of Object.keys(stats.compilation.assets)) {
        assets[name] = compiler.outputFileSystem.readFileSync(path.join(outDir, name), 'utf-8');
      }

      resolve(assets);
    });
  });
};

/**
 * The `addChunkModule(chunkId, moduleId, namespace, urlPath)` calls in a
 * bundle, in the order they run.
 */
export function registrationsIn(source = '') {
  return [...source.matchAll(/addChunkModule\('([^']*)',\s*'([^']*)',\s*'([^']*)',\s*'([^']*)'\)/g)].map(
    ([, chunkId, moduleId, namespace, urlPath]) => ({
      chunkId,
      moduleId,
      namespace,
      urlPath,
    })
  );
}
