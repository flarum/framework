export default function getCategorizedExtensions() {
  let extensions = {};

  Object.keys(app.data.extensions).map((id) => {
    const extension = app.data.extensions[id];
    let category = extension.extra['flarum-extension'].category;

    if (!extension.extra['flarum-extension'].category) {
      category = 'other';
    }

    // Wrap languages packs into new system
    if (extension.extra['flarum-locale']) {
      category = 'language';
    }

    if (category in app.extensionCategories) {
      extensions[category] = extensions[category] || {};

      extensions[category][id] = extension;
    } else {
      // If the extension doesn't fit
      // into a category add it to other
      extensions.other[id] = extension;
    }
  });

  return extensions;
}
