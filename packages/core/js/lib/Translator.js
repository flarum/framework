import User from 'flarum/models/User';
import username from 'flarum/helpers/username';
import extractText from 'flarum/utils/extractText';
import extract from 'flarum/utils/extract';

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
   * @param {VirtualElement} fallback
   * @return {VirtualElement}
   */
  trans(key, input = {}, fallback) {
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
    if (translation && typeof translation === 'object' && typeof input.count !== 'undefined') {
      translation = translation[this.plural(extractText(input.count))];
    }

    // If we've been given a user model as one of the input parameters, then
    // we'll extract the username and use that for the translation. In the
    // future there should be a hook here to inspect the user and change the
    // translation key. This will allow a gender property to determine which
    // translation key is used.
    if ('user' in input) {
      const user = extract(input, 'user');

      if (!input.username) input.username = username(user);
    }

    // If we've found the appropriate translation string, then we'll sub in the
    // input.
    if (typeof translation === 'string') {
      translation = translation.split(new RegExp('({[^}]+})', 'gi'));

      translation.forEach((part, i) => {
        const match = part.match(/^{(.+)}$/i);
        if (match) {
          translation[i] = input[match[1]];
        }
      });

      return translation;
    }

    return fallback || [key];
  }
}
