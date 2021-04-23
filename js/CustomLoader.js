// Hi early reviewer! I'm a temporary file and
// will be moved to the Flarum webpack config soon!

const loaderUtils = require('loader-utils');

module.exports = function (source) {
  // Custom loader logic

  // Exclude asynchronous modules
  if (!source.includes('//Flarum Asynchronous Module')) {

    // Get the type of the module to be exported
    const location = loaderUtils.interpolateName(this, '[folder]/', {
      context: this.rootContext || this.context,
    });

    // Get the name of module to be exported
    const moduleName = loaderUtils.interpolateName(this, '[name]', {
      context: this.rootContext || this.context,
    });

    // Don't export low level files
    if (/.*\/(admin|forum)$/.test(location) || /(index|app|compat|FlarumRegistry)$/.test(moduleName)) {
      return source;
    }

    let addition = "";

    // Find the export names
    const matches = [...source.matchAll(/export\s+?(?:default\s?|function|abstract\s?|class)+?\s([^(\s<;]*)/gm)];
    matches.map(match => {
      let name = match[1]

      if (!name || name === 'interface') {
        return;
      }

      // Add code at the end of the file to add the file to registry
      addition += `\nwindow.flreg.add('${location}${name}', ${name})`
    });

    return source + addition;
  } else {
    return source;
  }
}
