/**
 * Auto Export Loader
 *
 * This loader will automatically pick up all core and extension exports and add them to the registry.
 */

const path = require('path');
const fs = require('fs');
const { validate } = require('schema-utils');
const { getOptions, interpolateName } = require('loader-utils');
const extensionId = require('./extensionId.cjs');

const optionsSchema = {
  type: 'object',
  properties: {
    extension: {
      type: 'string',
    },
  },
};

let namespace;

function addAutoExports(source, pathToModule, moduleName) {
  let addition = '';

  const defaultExportMatches = [...source.matchAll(/export\s+?default\s(?:abstract\s)?(?:(?:function|abstract|class)\s)?([A-Za-z_]*)/gm)];
  const defaultExport = defaultExportMatches.length ? defaultExportMatches[0][1] : null;

  // In case of an index.js file that exports multiple modules
  // we need to add the directory as a module.
  // For an example checkout the `common/extenders/index.js` file.
  if (moduleName === 'index') {
    const id = pathToModule.substring(0, pathToModule.length - 1);

    // Add code at the end of the file to add the file to registry
    addition += `\nflarum.reg.add('${namespace}', '${id}', ${defaultExport});`;
  }

  // In a normal case, we do one of two things:
  else {
    // 1. If there is a default export, we add the module to the registry with the default export.
    //    Example: `export default class Foo {}` will be added to the registry as `Foo`,
    //              and can be imported using `import Foo from 'flarum/../Foo'`.
    if (defaultExport) {
      // Add code at the end of the file to add the file to registry
      addition += `\nflarum.reg.add('${namespace}', '${pathToModule}${moduleName}', ${defaultExport});`;
    }

    // 2. If there is no default export, then there are named exports,
    //    so we add the module to the registry with the map of named exports.
    //    Example: `export class Foo {}` will be added to the registry as `{ Foo: 'Foo' }`,
    //              and can be imported using `import { Foo } from 'flarum/../Foo'`,
    //              (checkout the `common/utils/string.ts` file for an example).
    else {
      // Another two case scenarios is when using `export { A, B } from 'x'`.
      // 2.1. If there is a default export, we add the module to the registry with the default export and ignore the named exports.
      //      Example: `export { nanoid as default, x } from 'nanoid'` will be added to the registry as `nanoid`,
      //                and can be imported using `import nanoid from 'flarum/../nanoid'`. x will be ignored.
      const objectExportWithDefaultMatches = [...source.matchAll(/export\s+?{.*as\s+?default.*}\s+?from\s+?['"](.*)['"]/gm)];

      if (objectExportWithDefaultMatches.length) {
        let objectDefaultExport = null;

        source = source.replace(/export\s+?{\s?([A-z_0-9]*)\s?as\s+?default.*}\s+?from\s+?['"](.*)['"]/gm, (match, defaultExport, path) => {
          objectDefaultExport = defaultExport;

          return `import { ${defaultExport} } from '${path}';\nexport default ${defaultExport}`;
        });

        addition += `\nflarum.reg.add('${namespace}', '${pathToModule}${moduleName}', ${objectDefaultExport});`;
      }
      // 2.2. If there is no default export, check for direct exports from other modules.
      //      We add the module to the registry with the map of named exports.
      //      Example: `export { A, B } from 'nanoid'` will be added to the registry as `{ A, B }`,
      //                and can be imported using `import { A, B } from 'flarum/../nanoid'`.
      else {
        const exportCurlyPattern = /export\s+?{(.*)}\s+?from\s+?['"](.*)['"]/gm;
        const namedExportMatches = [...source.matchAll(exportCurlyPattern)];

        if (namedExportMatches.length) {
          source = source.replaceAll(exportCurlyPattern, (match, names, path) => {
            return names
              .split(',')
              .map((name) => `import { ${name} } from '${path}';\nexport { ${name} }`)
              .join('\n');
          });

          // Addition to the registry is taken care of in step 2.3
        }
      }

      // 2.3. Finally, we check for all named exports
      //      these can be `export function|class|enum|.. Name ..`
      //      or `export { ... };
      {
        const matches = [...source.matchAll(/export\s+?(?:\* as|function|{\s*([A-z0-9, ]+)+\s?}|const|let|abstract\s?|class)+?\s?([A-Za-z_]*)?/gm)];

        if (matches.length) {
          const map = matches.reduce((map, match) => {
            const names = match[1] ? match[1].split(',') : (match[2] ? [match[2]] : null);

            if (!names) {
              return map;
            }

            for (let name of names) {
              name = name.trim();

              if (name === 'interface' || name === '') {
                continue;
              }

              map += `${name}: ${name},`;
            }

            return map;
          }, '');

          // Add code at the end of the file to add the file to registry
          if (map) addition += `\nflarum.reg.add('${namespace}', '${pathToModule}${moduleName}', { ${map} });`;
        }
      }
    }
  }

  return source + addition;
}

// Custom loader logic
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
    namespace = extensionId(composerJson.name);
  }

  // Get the type of the module to be exported
  const location = interpolateName(this, '[folder]/', {
    context: this.rootContext || this.context,
  });

  // Get the name of module to be exported
  const moduleName = interpolateName(this, '[name]', {
    context: this.rootContext || this.context,
  });

  // Don't export low level files
  if ((/(admin|forum)\/$/.test(location) && moduleName !== 'app') || /(compat|ExportRegistry|registry)$/.test(moduleName)) {
    return source;
  }

  // Don't export index.js of common
  if (moduleName === 'index' && location === 'common/') {
    return source;
  }

  // Don't export extend.js of extensions
  if (namespace !== 'core' && /extend$/.test(moduleName)) {
    return source;
  }

  // Get the path of the module to be exported
  // relative to the src directory.
  // Example: src/forum/components/UserCard.js => forum/components
  const pathToModule = path.relative(path.resolve(this.rootContext, 'src'), this.resourcePath)
    .replaceAll(path.sep, '/')
    .replace(/[A-Za-z_]+\.[A-Za-z_]+$/, '');

  return addAutoExports(source, pathToModule, moduleName);
};
