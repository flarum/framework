const path = require("path");
const extensionId = require("./extensionId.cjs");
const {Compilation} = require("webpack");

class RegisterAsyncChunksPlugin {
  apply(compiler) {
    // console log the contents of the LogInModal module
    compiler.hooks.thisCompilation.tap("RegisterAsyncChunksPlugin", (compilation) => {
      let alreadyOptimized = false;
      compilation.hooks.unseal.tap("RegisterAsyncChunksPlugin", () => {
        alreadyOptimized = false;
      });

      compilation.hooks.processAssets.tap(
        {
          name: "RegisterAsyncChunksPlugin",
          stage: Compilation.PROCESS_ASSETS_STAGE_ADDITIONAL,
        },
        () => {
          if (alreadyOptimized) return;
          alreadyOptimized = true;

          const chunks = Array.from(compilation.chunks);

          for (const chunk of chunks) {
            for (const module of compilation.chunkGraph.getChunkModulesIterable(chunk)) {
              // If the module source has an async webpack chunk, add the chunk id to flarum.reg
              // at the end of the module source.

              if (module.resource && module.resource.includes("/src/") && module._source._value.includes("webpackChunkName: ")) {
                const reg = [];

                module._source._value.replaceAll(/^(.*) webpackChunkName: '([^']*)'.* \*\/ '(.*)'.*$/gm, (match, _, urlPath, importPath) => {
                  // Import path is relative to module.resource, so we need to resolve it
                  const importPathResolved = path.resolve(path.dirname(module.resource), importPath);
                  const relevantChunk = chunks.find((chunk) => compilation.chunkGraph.getChunkModules(chunk).find((module) => module.resource?.includes(importPathResolved)));
                  const thisComposerJson = require(path.resolve(process.cwd(), '../composer.json'));
                  const namespace = extensionId(thisComposerJson.name);

                  reg.push(`flarum.reg.addChunk('${relevantChunk.id}', '${namespace}', '${urlPath}');`);

                  return `${match}`;
                });

                module._source._value += reg.join('\n');
              }
            }
          }
        }
      );
    });
  }
}

module.exports = RegisterAsyncChunksPlugin;
