/**
 * This plugin overrides the webpack chunk loader function `__webpack_require__.l` which is a webpack constant
 * with `flarum.reg.loadChunk`, which resides in the flarum app.
 *
 * Chunk ids are only unique within one build, so the runtime tells the
 * registry which extension is asking (flarum/framework#5027).
 */
const path = require('path');
const extensionId = require('./extensionId.cjs');

class OverrideChunkLoaderFunction {
  apply(compiler) {
    const thisComposerJson = require(path.resolve(process.cwd(), '../composer.json'));
    const namespace = extensionId(thisComposerJson.name);

    // We don't want to literally override its source.
    // We want to override the function that is called by webpack.
    // By adding a new line to reassign the function to our own function.
    // The function is called by webpack so we can't just override it.
    compiler.hooks.compilation.tap('OverrideChunkLoaderFunction', (compilation) => {
      compilation.mainTemplate.hooks.requireEnsure.tap('OverrideChunkLoaderFunction', (source) => {
        return (
          source +
          '\nconst originalLoadChunk = __webpack_require__.l;' +
          '\n__webpack_require__.l = (url, done, key, chunkId) => flarum.reg.loadChunk(originalLoadChunk, url, done, key, chunkId, ' +
          JSON.stringify(namespace) +
          ');'
        );
      });

      // log webpack runtime after the final code, the hook isn't requireEnsure
      if (namespace !== 'core') {
        compilation.mainTemplate.hooks.require.tap('OverrideChunkLoaderFunction', (source) => {
          return 'flarum.reg._webpack_runtimes["' + namespace + '"] ||= __webpack_require__;' + source;
        });
      }
    });
  }
}

module.exports = OverrideChunkLoaderFunction;
