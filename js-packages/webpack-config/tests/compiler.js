import path from 'path';
import webpack from 'webpack';
import { createFsFromVolume, Volume } from 'memfs';
import * as fs from 'fs';

export default (fixture, options = {}) => {
  const compiler = webpack({
    context: __dirname,
    entry: `./${fixture}`,
    output: {
      path: path.resolve(__dirname),
      filename: 'bundle.js',
    },
    module: {
      rules: [
        {
          test: /\.js$/,
          use: {
            loader: path.resolve(__dirname, '../src/autoExportLoader.cjs'),
            options: {
              ...options,
              composerPath: '../../composer.json',
            },
          },
        },
      ],
    },
    optimization: {
      minimize: false,
      minimizer: [],
    },
  });

  compiler.outputFileSystem = createFsFromVolume(new Volume());
  compiler.outputFileSystem.join = path.join.bind(path);

  return new Promise((resolve, reject) => {
    compiler.run((err, stats) => {
      if (err) reject(err);
      if (stats.hasErrors()) reject(stats.toJson().errors);

      const outputFilepath = path.join(compiler.options.output.path, compiler.options.output.filename);
      stats.finalOutput = compiler.outputFileSystem.readFileSync(outputFilepath, 'utf-8');

      resolve(stats);
    });
  });
};
