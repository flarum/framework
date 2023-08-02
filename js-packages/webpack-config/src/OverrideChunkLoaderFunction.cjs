/**
 * This plugin overrides the webpack chunk loader function `__webpack_require__.l` which is a webpack constant
 * with `flarum.reg.loadChunk`, which resides in the flarum app.
 */

class OverrideChunkLoaderFunction {
  apply(compiler) {
    // We don't want to literally override its source.
    // We want to override the function that is called by webpack.
    // By adding a new line to reassing the function to our own function.
    // The function is called by webpack so we can't just override it.
    compiler.hooks.compilation.tap('OverrideChunkLoaderFunction', (compilation) => {
      compilation.mainTemplate.hooks.requireEnsure.tap('OverrideChunkLoaderFunction', (source) => {
        return source + '\nconst originalLoadChunk = __webpack_require__.l;\n__webpack_require__.l = flarum.reg.loadChunk.bind(flarum.reg, originalLoadChunk);';
      });
    });
  }
}


module.exports = OverrideChunkLoaderFunction;
