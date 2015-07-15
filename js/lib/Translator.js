/**
 * The `Translator` class translates strings using the loaded localization.
 */
export default class Translator {
  constructor() {
    /**
     * A map of translation keys to their translated values.
     *
     * @type {Object}
     * @public
     */
    this.translations = {};
  }

  /**
   * Determine the key of a translation that should be used for the given count.
   * The default implementation is for English plurals. It should be overridden
   * by a locale's JavaScript file if necessary.
   *
   * @param {Integer} count
   * @return {String}
   * @public
   */
  plural(count) {
    return count === 1 ? 'one' : 'other';
  }

  /**
   * Translate a string.
   *
   * @param {String} key
   * @param {Object} input
   * @return {String}
   */
  trans(key, input = {}) {
    const parts = key.split('.');
    let translation = this.translations;

    // Drill down into the translation tree to find the translation for this
    // key.
    parts.forEach(part => {
      translation = translation && translation[part];
    });

    // If this translation has multiple options and a 'count' has been provided
    // in the input, we'll work out which option to choose using the `plural`
    // method.
    if (typeof translation === 'object' && typeof input.count !== 'undefined') {
      translation = translation[this.plural(input.count)];
    }

    // If we've found the appropriate translation string, then we'll sub in the
    // input.
    if (typeof translation === 'string') {
      for (const i in input) {
        translation = translation.replace(new RegExp('{' + i + '}', 'gi'), input[i]);
      }

      return translation;
    }

    return key;
  }
}
