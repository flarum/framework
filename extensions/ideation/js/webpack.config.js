const config = require('flarum-webpack-config');

const NS = 'flarum-ideation';

/*
 * 🚨 Lazy chunks need ids no other extension can have.
 *
 * Flarum's registry finds a chunk's URL by its id alone, and webpack's default
 * ids are 3-digit hashes of the chunk's path, so two extensions can share one:
 * the wrong file is fetched, or the chunk counts as already loaded, and the
 * page dies. It only shows on a forum running both. So: ids prefixed with this
 * extension's id, and a chunk-loading global of its own.
 */
class NamespacedChunkIds {
  apply(compiler) {
    compiler.hooks.compilation.tap('NamespacedChunkIds', (compilation) => {
      compilation.hooks.chunkIds.tap('NamespacedChunkIds', (chunks) => {
        for (const chunk of chunks) {
          if (chunk.id === null) {
            chunk.id = `${NS}:${chunk.name || chunk.debugId}`;
            chunk.ids = [chunk.id];
          }
        }
      });
    });
  }
}

module.exports = (...args) => {
  let base = config();
  if (typeof base === 'function') base = base(...args);

  base.optimization = { ...base.optimization, chunkIds: false };
  base.output = { ...base.output, chunkLoadingGlobal: 'webpackChunk_flarum_ideation' };
  base.plugins = [...(base.plugins || []), new NamespacedChunkIds()];

  return base;
};
