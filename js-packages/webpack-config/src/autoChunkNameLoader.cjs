const path = require("path");
const {getOptions} = require("loader-utils");
const {validate} = require("schema-utils");
const fs = require("fs");

const optionsSchema = {
  type: 'object',
  properties: {
    extension: {
      type: 'string',
    },
  },
};

let namespace;

module.exports = function autoExportLoader(source) {
  const options = getOptions(this) || {};

  validate(optionsSchema, options, {
    name: 'Flarum Webpack Loader',
    composerPath: 'Path to the extension composer.json file',
  });

  // Ensure that composer.json is watched for changes
  // so that the loader is run again when composer.json
  // is updated.
  const composerJsonPath = path.resolve(options.composerPath || '../composer.json');
  this.addDependency(composerJsonPath);

  // Get the namespace of the module to be exported
  // the namespace is essentially just the usual extension ID.
  if (!namespace) {
    const composerJson = JSON.parse(fs.readFileSync(composerJsonPath, 'utf8'));

    // Get the value of the 'name' property
    namespace =
      composerJson.name === 'flarum/core' ? 'core' : composerJson.name.replace('/flarum-ext-', '-').replace('/flarum-', '').replace('/', '-');
  }

  // Get the absolute path to this module
  const pathToThisModule = this.resourcePath;

  // Replace all `import('path/to/module')` with `import(/* webpackChunkName: "relative/path/to/module/from/src" */ 'relative/path/to/module')`.
  const importPattern = /(^(?!\s+\* @.*).*)import\(['"](.*)['"]\)/gm;
  const matches = [...source.matchAll(importPattern)];

  if (matches.length) {
    source = source.replaceAll(importPattern, (match, pre, relativePathToImport) => {
      // Compute the absolute path from src to the module being imported
      // based on the path of the file being imported from.
      const absolutePathToImport = path.resolve(path.dirname(pathToThisModule), relativePathToImport);
      let chunkPath = relativePathToImport;

      if (absolutePathToImport.includes('src')) {
        chunkPath = absolutePathToImport.split('src/')[1];
      }

      const webpackCommentOptions = {
        webpackChunkName: chunkPath,
        webpackMode: 'lazy-once',
      };

      const comment = Object.entries(webpackCommentOptions).map(([key, value]) => `${key}: '${value}'`).join(', ');

      // Return the new import statement
      return `${pre}import(/* ${comment} */ '${relativePathToImport}')`;
    });
  }

  return source;
};
